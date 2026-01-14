let emailChecked = false;

const email = document.getElementById('email');
const emailMsg = document.getElementById('emailMsg');
const password = document.getElementById('password');
const passwordConfirm = document.getElementById('passwordConfirm');
const pwMsg = document.getElementById('pwMsg');
const pwConfirmMsg = document.getElementById('pwConfirmMsg');
const agree = document.getElementById('agree');
const submitBtn = document.getElementById('submitBtn');
const form = document.getElementById('registerForm');

document.getElementById('checkEmail').onclick = async () => {
  const res = await fetch('../../api/auth/check_email.php', {
    method: 'POST',
    body: new URLSearchParams({ email: email.value })
  });
  const data = await res.json();

  if (data.available) {
    emailMsg.textContent = '사용할 수 있는 아이디입니다.';
    emailChecked = true;
  } else {
    emailMsg.textContent = '사용할 수 없는 아이디입니다.';
    emailChecked = false;
  }
  validate();
};

function validate() {
  const pw = password.value;
  const regex = /^(?=.*[A-Za-z])(?=.*\d)(?=.*[!@#]).{8,20}$/;

  pwMsg.textContent = regex.test(pw) ? '' : '사용할 수 없는 비밀번호 입니다.';
  pwConfirmMsg.textContent =
    pw === passwordConfirm.value ? '' : '비밀번호가 일치하지 않습니다.';

  submitBtn.disabled = !(
    emailChecked &&
    regex.test(pw) &&
    pw === passwordConfirm.value &&
    agree.checked
  );
}

document.querySelectorAll('input').forEach(i => {
  i.addEventListener('input', validate);
});

submitBtn.onclick = async () => {
  const formData = new FormData(form);

  const res = await fetch('../../api/auth/register.php', {
    method: 'POST',
    body: formData
  });

  const data = await res.json();
  alert(data.message);

  if (data.success) {
    location.href = 'login.html';
  }
};
