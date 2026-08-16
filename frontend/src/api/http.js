const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080';
const TOKEN_KEY = 'crm.jwt';

export function setToken(token) {
  window.localStorage.setItem(TOKEN_KEY, token);
}

export function getToken() {
  return window.localStorage.getItem(TOKEN_KEY);
}

export function clearToken() {
  window.localStorage.removeItem(TOKEN_KEY);
}

export class ApiError extends Error {
  constructor(message, status, errors) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.errors = errors || {};
  }
}

function buildQuery(params) {
  const search = new URLSearchParams();

  Object.keys(params || {}).forEach((key) => {
    const value = params[key];

    if (value === null || value === undefined || value === '' || value === false) {
      return;
    }

    search.append(key, value === true ? '1' : String(value));
  });

  const query = search.toString();

  return query ? `?${query}` : '';
}

async function request(method, path, { params, body } = {}) {
  const headers = { Accept: 'application/json' };
  const token = getToken();

  if (token) {
    headers.Authorization = `Bearer ${token}`;
  }

  if (body !== undefined) {
    headers['Content-Type'] = 'application/json';
  }

  const response = await fetch(`${API_BASE_URL}${path}${buildQuery(params)}`, {
    method,
    headers,
    body: body === undefined ? undefined : JSON.stringify(body),
  });

  const text = await response.text();
  const data = text ? JSON.parse(text) : null;

  if (!response.ok) {
    const message = (data && data.message) || `Ошибка запроса (${response.status})`;

    if (response.status === 401) {
      clearToken();
    }

    throw new ApiError(message, response.status, data && data.errors);
  }

  return data;
}

export const http = {
  get: (path, params) => request('GET', path, { params }),
  post: (path, body) => request('POST', path, { body: body || {} }),
  put: (path, body) => request('PUT', path, { body: body || {} }),
  del: (path) => request('DELETE', path),
};
