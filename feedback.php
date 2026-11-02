<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Share feedback';
$feedbackFile = __DIR__ . '/feedback.txt';
if (!file_exists($feedbackFile) && file_put_contents($feedbackFile, '') === false) {
    throw new RuntimeException('Could not create feedback storage.');
}

function read_feedback(string $file): array
{
    $snapshot = file_get_contents($file);
    if ($snapshot === false) throw new RuntimeException('Could not read feedback storage.');
    $handle = fopen($file, 'rb');
    if ($handle === false) throw new RuntimeException('Could not open feedback storage for reading.');
    if (!flock($handle, LOCK_SH)) { fclose($handle); throw new RuntimeException('Could not lock feedback storage.'); }
    $content = '';
    while (!feof($handle)) {
        $chunk = fread($handle, 8192);
        if ($chunk === false) {
            flock($handle, LOCK_UN);
            fclose($handle);
            throw new RuntimeException('Could not read feedback storage.');
        }
        $content .= $chunk;
    }
    flock($handle, LOCK_UN);
    fclose($handle);
    if ($content !== $snapshot) $snapshot = $content;
    if (trim($snapshot) === '') return [];
    $decoded = json_decode($snapshot, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($decoded)) throw new RuntimeException('Feedback storage has an invalid format.');
    return $decoded;
}

function write_feedback(string $file, array $rows): void
{
    $json = json_encode(array_values($rows), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    $handle = fopen($file, 'c+b');
    if ($handle === false) throw new RuntimeException('Could not open feedback storage for writing.');
    if (!flock($handle, LOCK_EX)) { fclose($handle); throw new RuntimeException('Could not lock feedback storage.'); }
    if (!ftruncate($handle, 0)) {
        flock($handle, LOCK_UN);
        fclose($handle);
        throw new RuntimeException('Could not clear feedback storage.');
    }
    rewind($handle);
    if (fwrite($handle, $json) !== strlen($json) || !fflush($handle)) {
        flock($handle, LOCK_UN);
        fclose($handle);
        throw new RuntimeException('Could not save feedback.');
    }
    flock($handle, LOCK_UN);
    fclose($handle);
}

$feedback = read_feedback($feedbackFile);
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? 'create');
    $name = trim((string) ($_POST['name'] ?? ''));
    $message = trim((string) ($_POST['message'] ?? ''));
    $id = (string) ($_POST['id'] ?? '');
    if (!in_array($action, ['create','edit','delete'], true)) {
        $error = 'That feedback action is not available.';
    } elseif ($action === 'delete' && $id === '') {
        $error = 'Choose a feedback entry to remove.';
    }
    if ($error === '' && $action === 'delete') {
        $feedback = array_values(array_filter($feedback, fn($row) => ($row['id'] ?? '') !== $id));
        write_feedback($feedbackFile, $feedback);
        flash('Feedback removed.', 'info');
        redirect('feedback.php');
    } elseif ($error === '' && !valid_name($name)) {
        $error = 'Please use a valid name (letters and spaces).';
    } elseif ($error === '' && (mb_strlen($message) < 8 || mb_strlen($message) > 1000)) {
        $error = 'Feedback must be between 8 and 1000 characters.';
    } elseif ($error === '') {
        $record = ['id' => $action === 'edit' ? $id : bin2hex(random_bytes(8)), 'name' => $name, 'message' => $message, 'updated_at' => date(DATE_ATOM)];
        if ($action === 'edit') {
            $found = false;
            foreach ($feedback as $index => $row) {
                if (($row['id'] ?? '') === $id) {
                    $feedback[$index] = $record;
                    $found = true;
                }
            }
            if (!$found) {
                $error = 'That feedback entry no longer exists.';
            } else {
                write_feedback($feedbackFile, $feedback);
                flash('Your feedback was updated.');
                redirect('feedback.php');
            }
        } else {
            array_unshift($feedback, $record);
            write_feedback($feedbackFile, $feedback);
            flash('Thank you for sharing your thoughts.');
            redirect('feedback.php');
        }
    }
}
$editing = null;
if (isset($_GET['edit'])) foreach ($feedback as $row) if ($row['id'] === $_GET['edit']) $editing = $row;
require __DIR__ . '/includes/header.php';
?>
<div class="container"><div class="page-intro"><div class="eyebrow">Help us make it better</div><h1>Share your feedback.</h1><p>This module demonstrates PHP text-file CRUD; feedback is stored locally in feedback.txt.</p></div>
<div class="checkout-grid"><section class="panel"><h2><?= $editing ? 'Edit feedback' : 'Leave a note' ?></h2><?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" data-validate-form novalidate><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="<?= $editing ? 'edit' : 'create' ?>"><input type="hidden" name="id" value="<?= e($editing['id'] ?? '') ?>">
        <div class="form-field"><label for="name">Name</label><input id="name" name="name" required value="<?= e($editing['name'] ?? $_POST['name'] ?? '') ?>"><div class="field-error" data-error="name"></div></div>
        <div class="form-field"><label for="message">Your feedback</label><textarea id="message" name="message" rows="5" required><?= e($editing['message'] ?? $_POST['message'] ?? '') ?></textarea></div>
        <button class="button" type="submit"><?= $editing ? 'Save changes' : 'Send feedback' ?></button><?php if ($editing): ?> <a class="button-outline" href="feedback.php">Cancel</a><?php endif; ?>
    </form>
</section><section><h2>Community notes</h2><?php if (!$feedback): ?><div class="empty-state"><p>No feedback yet. Be the first to share.</p></div><?php else: foreach ($feedback as $row): ?><article class="feedback-card"><strong><?= e($row['name']) ?></strong><span style="float:right;font-size:10px;color:#999"><?= e(date('M j, Y', strtotime($row['updated_at']))) ?></span><p><?= nl2br(e($row['message'])) ?></p><a class="text-link" href="feedback.php?edit=<?= e($row['id']) ?>">Edit</a>
    <form method="post" style="display:inline;margin-left:12px"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= e($row['id']) ?>"><button class="text-link" style="border:0;background:none">Delete</button></form>
</article><?php endforeach; endif; ?></section></div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
