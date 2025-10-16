(() => {
  const form = document.querySelector('#login-form');
  if (!form) return;

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const email = form.querySelector('input[name="email"]').value;
    const password = form.querySelector('input[name="password"]').value;
    const message = form.querySelector('.message');
    message.textContent = '로그인 중...';

    try {
      const response = await App.request('POST', '/login', { email, password });
      App.setToken(response.token);
      message.textContent = `${response.user.name}님 환영합니다.`;
      window.location.href = '/dashboard.html';
    } catch (error) {
      message.textContent = error.message || '로그인에 실패했습니다.';
    }
  });
})();
