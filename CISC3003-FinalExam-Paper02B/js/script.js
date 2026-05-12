/**
 * Scenario B — B.01 extra client-side validation before POST.
 */
function showErrors(form, messages) {
  let box = form.querySelector('.js-errors');
  if (!box) {
    box = document.createElement('div');
    box.className = 'flash err js-errors';
    form.prepend(box);
  }
  box.innerHTML = '<ul>' + messages.map((m) => `<li>${m}</li>`).join('') + '</ul>';
}

function validateContact(form) {
  const messages = [];
  const name = form.querySelector('#sender_name');
  const email = form.querySelector('#sender_email');
  const subject = form.querySelector('#subject');
  const message = form.querySelector('#message');

  if (name && name.value.trim().length < 2) messages.push('Name should be at least 2 characters.');
  if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) messages.push('Email looks invalid.');
  if (subject && subject.value.trim().length < 3) messages.push('Subject should be at least 3 characters.');
  if (message && message.value.trim().length < 10) messages.push('Message should be at least 10 characters.');

  return messages;
}

document.addEventListener('DOMContentLoaded', () => {
  const contact = document.getElementById('contact-form');
  if (contact) {
    contact.addEventListener('submit', (e) => {
      const msgs = validateContact(contact);
      if (msgs.length) {
        e.preventDefault();
        showErrors(contact, msgs);
      }
    });
  }

  const reg = document.getElementById('register-interest-form');
  if (reg) {
    reg.addEventListener('submit', (e) => {
      const msgs = validateContact(reg);
      if (msgs.length) {
        e.preventDefault();
        showErrors(reg, msgs);
      }
    });
  }
});
