const form = document.getElementById('loginForm');
const toast = document.getElementById('toast');

function showToast(message, success = false) {
  toast.textContent = message;
  toast.className = success ? 'toast success' : 'toast error';
  toast.style.display = 'block';

  setTimeout(() => {
    toast.style.display = 'none';
  }, 3000);
}

form.addEventListener('submit', async (e) => {
  e.preventDefault();

  try {
    const formData = new FormData(form);

    const res = await fetch('/easyj/api/auth/login', {
      method: 'POST',
      body: formData,
      credentials: 'same-origin'
    });

    const data = await res.json();

    showToast(data.message, data.success);

    if (data.success) {
      setTimeout(() => {
        location.href = '../contract/index.php';
      }, 1000);
    }

  } catch (err) {
    console.error(err);
    showToast('서버 오류가 발생했습니다.');
  }
});
