/**
 * Scenario C — C.05 browser validation + C.06 Ajax email check.
 */

function attachSignupValidation() {
  const form = document.getElementById('signup-form');
  if (!form) return;

  const email = document.getElementById('email');
  const hint = document.getElementById('email-ajax-hint');
  let timer = null;

  async function checkEmailAjax(value) {
    if (!hint) return;
    hint.textContent = '';
    hint.classList.remove('email-bad', 'email-good');
    const trimmed = value.trim();
    if (!trimmed.includes('@')) {
      return;
    }
    const url = `ajax_email.php?email=${encodeURIComponent(trimmed)}`;
    try {
      const res = await fetch(url, { headers: { Accept: 'application/json' } });
      const data = await res.json();
      hint.textContent = data.message || '';
      hint.classList.toggle('email-bad', data.available === false);
      hint.classList.toggle('email-good', data.available === true);
    } catch (e) {
      hint.textContent = 'Could not verify email right now.';
      hint.classList.add('email-bad');
    }
  }

  if (email) {
    email.addEventListener('input', () => {
      window.clearTimeout(timer);
      timer = window.setTimeout(() => checkEmailAjax(email.value), 350);
    });
    email.addEventListener('blur', () => checkEmailAjax(email.value));
  }

  form.addEventListener('submit', (e) => {
    const name = document.getElementById('full_name');
    const p1 = document.getElementById('password');
    const p2 = document.getElementById('password_confirm');
    const msgs = [];
    if (name && name.value.trim().length < 2) msgs.push('Full name should be at least 2 characters.');
    if (p1 && p1.value.length < 8) msgs.push('Password should be at least 8 characters.');
    if (p1 && p2 && p1.value !== p2.value) msgs.push('Passwords do not match.');
    if (msgs.length) {
      e.preventDefault();
      alert(msgs.join('\n'));
    }
  });
}

document.addEventListener('DOMContentLoaded', () => {
  const signupBtn = document.getElementById('btn-go-register');
  const signinBtn = document.getElementById('btn-go-login');
  if (signupBtn) signupBtn.addEventListener('click', () => { window.location.href = 'php/register.php'; });
  if (signinBtn) signinBtn.addEventListener('click', () => { window.location.href = 'php/login.php'; });

  const loginForm = document.getElementById('login-form');
  if (loginForm) {
    loginForm.addEventListener('submit', (e) => {
      const emailEl = document.getElementById('email');
      const pw = document.getElementById('password');
      const msgs = [];
      if (emailEl && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailEl.value.trim())) {
        msgs.push('Please enter a plausible email address.');
      }
      if (pw && pw.value.length < 8) {
        msgs.push('Password should be at least 8 characters.');
      }
      if (msgs.length) {
        e.preventDefault();
        alert(msgs.join('\n'));
      }
    });
  }

  attachSignupValidation();

  const cp = document.getElementById('change-password-form');
  if (cp) {
    cp.addEventListener('submit', (e) => {
      const a = document.getElementById('new_password');
      const b = document.getElementById('new_password_confirm');
      if (a && b && a.value !== b.value) {
        e.preventDefault();
        alert('New passwords do not match.');
      }
    });
  }
});
