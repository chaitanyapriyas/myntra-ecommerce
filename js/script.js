document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('img').forEach(image => {
    image.addEventListener('error', () => {
      image.onerror = null;
      image.src = 'https://images.unsplash.com/photo-1483985988355-763728e1935b?auto=format&fit=crop&w=800&q=80';
    }, { once: true });
  });
  const menu = document.querySelector('.menu-toggle');
  const nav = document.querySelector('.main-nav');
  if (menu && nav) menu.addEventListener('click', () => {
    const open = nav.classList.toggle('is-open');
    menu.setAttribute('aria-expanded', String(open));
  });

  document.querySelectorAll('[data-validate-form]').forEach(form => {
    const rules = {
      name: [/^[A-Za-zÀ-ÿ ]{2,80}$/, 'Use letters and spaces only (at least 2 characters).'],
      email: [/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/, 'Enter a valid email address.'],
      password: [/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/, 'Use 8+ characters with uppercase, lowercase and a digit.'],
      pin: [/^\d{6}$/, 'Enter a six-digit PIN code.']
    };
    const validate = input => {
      const rule = rules[input.name];
      const error = form.querySelector(`[data-error="${input.name}"]`) || input.closest('.form-field')?.querySelector('.field-error');
      if (input.required && !input.value.trim()) {
        if (error) error.textContent = 'This field is required.';
        input.setAttribute('aria-invalid', 'true');
        return false;
      }
      if (!rule || !error) return true;
      const valid = rule[0].test(input.value.trim());
      error.textContent = valid ? '' : rule[1];
      input.setAttribute('aria-invalid', String(!valid));
      return valid;
    };
    form.querySelectorAll('input,textarea,select').forEach(input => input.addEventListener('input', () => validate(input)));
    form.addEventListener('submit', event => {
      const valid = [...form.querySelectorAll('input,textarea,select')].every(validate);
      if (!valid) event.preventDefault();
    });
  });

  const filterForm = document.querySelector('[data-live-filter]');
  if (filterForm) {
    const cards = [...document.querySelectorAll('[data-product-card]')];
    const apply = () => {
      const query = (filterForm.querySelector('[name="q"]')?.value || '').toLowerCase();
      const category = filterForm.querySelector('[name="category"]')?.value || '';
      cards.forEach(card => {
        const match = (!query || card.dataset.search.includes(query)) &&
          (!category || card.dataset.category === category);
        card.hidden = !match;
      });
    };
    filterForm.querySelectorAll('input,select').forEach(field => field.addEventListener('input', apply));
    apply();
  }

  document.querySelectorAll('[data-demo-coordinates]').forEach(box => {
    box.addEventListener('mousemove', event => {
      box.textContent = `Client: ${event.clientX}, ${event.clientY}  |  Screen: ${event.screenX}, ${event.screenY}`;
    });
    box.addEventListener('click', () => box.classList.toggle('is-clicked'));
  });
  document.querySelectorAll('[data-overlap]').forEach(panel => {
    panel.addEventListener('mouseenter', () => {
      document.querySelectorAll('[data-overlap]').forEach(item => item.style.zIndex = '1');
      panel.style.zIndex = '2';
    });
  });
  const keyOutput = document.querySelector('[data-key-output]');
  if (keyOutput) document.addEventListener('keydown', event => {
    keyOutput.textContent = `Key: ${event.key} · Code: ${event.code}`;
  });
  const demoFilter = document.querySelector('[data-demo-filter]');
  if (demoFilter) demoFilter.addEventListener('input', () => {
    const query = demoFilter.value.trim().toLowerCase();
    document.querySelectorAll('[data-demo-product]').forEach(item => {
      item.hidden = !item.dataset.text.includes(query);
    });
  });
  document.querySelectorAll('[data-help-trigger]').forEach(trigger => {
    trigger.addEventListener('focus', () => document.querySelector('[data-help-box]')?.removeAttribute('hidden'));
    trigger.addEventListener('blur', () => document.querySelector('[data-help-box]')?.setAttribute('hidden', ''));
  });
});
