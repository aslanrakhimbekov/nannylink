const BASE_URL = (import.meta.env.VITE_API_BASE_URL || '') + '/api/v1';

async function request(path, options = {}) {
  const token = localStorage.getItem('nannylink_token');
  const lang = localStorage.getItem('nannylink_lang') || 'ru';

  const headers = {
    'Accept': 'application/json',
    'Accept-Language': lang,
    ...options.headers,
  };

  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  // Set Content-Type: application/json if body is not FormData
  if (options.body && !(options.body instanceof FormData)) {
    headers['Content-Type'] = 'application/json';
    options.body = JSON.stringify(options.body);
  }

  const response = await fetch(`${BASE_URL}${path}`, {
    ...options,
    headers,
  });

  if (response.status === 401) {
    localStorage.removeItem('nannylink_token');
    localStorage.removeItem('nannylink_user');
    window.dispatchEvent(new Event('nannylink_logout'));
    throw new Error('Unauthenticated');
  }

  const data = await response.json().catch(() => ({}));

  if (!response.ok) {
    let message = data.message;
    if (data.errors) {
      const firstKey = Object.keys(data.errors)[0];
      if (firstKey && Array.isArray(data.errors[firstKey]) && data.errors[firstKey][0]) {
        message = data.errors[firstKey][0];
      }
    }
    const error = new Error(message || 'API Error');
    error.status = response.status;
    error.errors = data.errors;
    throw error;
  }

  return data;
}

export const client = {
  get: (path, params) => {
    let query = '';
    if (params) {
      const activeParams = Object.fromEntries(
        Object.entries(params).filter(([_, v]) => v !== null && v !== undefined)
      );
      query = '?' + new URLSearchParams(activeParams).toString();
    }
    return request(`${path}${query}`, { method: 'GET' });
  },
  post: (path, body) => request(path, { method: 'POST', body }),
  put: (path, body) => request(path, { method: 'PUT', body }),
  patch: (path, body) => request(path, { method: 'PATCH', body }),
  delete: (path) => request(path, { method: 'DELETE' }),
  upload: (path, formData) => request(path, {
    method: 'POST',
    body: formData,
  }),
};
