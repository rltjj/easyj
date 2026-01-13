const form = document.getElementById('loginForm');
const loginBtn = document.getElementById('loginBtn');
const toast = document.getElementById('toast');

function showToast(message, success = false) {
  toast.textContent = message;
  toast.className = success ? 'toast success' : 'toast error';
  toast.style.display = 'block';

  setTimeout(() => {
    toast.style.display = 'none';
  }, 3000);
}

loginBtn.onclick = async () => {
  const formData = new FormData(form);

  const res = await fetch('../../api/auth/login.php', {
    method: 'POST',
    body: formData
  });

  const data = await res.json();

  showToast(data.message, data.success);

  if (data.success) {
    setTimeout(() => {
      location.href = '../contract/index.php';
    }, 1000);
  }
};
