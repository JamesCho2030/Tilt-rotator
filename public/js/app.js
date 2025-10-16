const App = (() => {
  const API_BASE = '/api';
  let token = localStorage.getItem('jk_token') || '';

  const request = async (method, url, data = null) => {
    const headers = {
      'Content-Type': 'application/json',
    };
    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
    }

    const response = await fetch(`${API_BASE}${url}`, {
      method,
      headers,
      body: data ? JSON.stringify(data) : null,
    });

    if (response.status === 204) {
      return null;
    }

    const body = await response.json().catch(() => ({}));
    if (!response.ok) {
      throw body;
    }
    return body;
  };

  const setToken = (newToken) => {
    token = newToken;
    if (token) {
      localStorage.setItem('jk_token', token);
    } else {
      localStorage.removeItem('jk_token');
    }
  };

  const getToken = () => token;

  const formatCurrency = (value) => {
    return Number(value || 0).toLocaleString('ko-KR', { style: 'currency', currency: 'KRW' });
  };

  return {
    request,
    setToken,
    getToken,
    formatCurrency,
  };
})();
