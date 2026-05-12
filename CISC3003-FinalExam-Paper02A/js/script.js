/**
 * Scenario A — light client-side behaviour for primary actions.
 */
document.addEventListener('DOMContentLoaded', () => {
  const signupBtn = document.getElementById('btn-go-register');
  const signinBtn = document.getElementById('btn-go-login');

  if (signupBtn) {
    signupBtn.addEventListener('click', () => {
      window.location.href = 'php/register.php';
    });
  }
  if (signinBtn) {
    signinBtn.addEventListener('click', () => {
      window.location.href = 'php/login.php';
    });
  }
});
