import { http } from './http';

export const authApi = {
  login: (email, password) => http.post('/api/auth/login', { email, password }),
  me: () => http.get('/api/auth/me'),
};

export const userApi = {
  list: () => http.get('/api/users'),
};

export const clientApi = {
  list: (params) => http.get('/api/clients', params),
  view: (id) => http.get(`/api/clients/${id}`),
  create: (payload) => http.post('/api/clients', payload),
  update: (id, payload) => http.put(`/api/clients/${id}`, payload),
  archive: (id) => http.del(`/api/clients/${id}`),
};

export const dealApi = {
  list: (params) => http.get('/api/deals', params),
  board: (params) => http.get('/api/deals/board', params),
  view: (id) => http.get(`/api/deals/${id}`),
  create: (payload) => http.post('/api/deals', payload),
  update: (id, payload) => http.put(`/api/deals/${id}`, payload),
  changeStage: (id, stage, comment) => http.post(`/api/deals/${id}/stage`, { stage, comment }),
  stages: () => http.get('/api/deals/stages'),
};

export const taskApi = {
  list: (params) => http.get('/api/tasks', params),
  create: (payload) => http.post('/api/tasks', payload),
  changeStatus: (id, status) => http.post(`/api/tasks/${id}/status`, { status }),
  statuses: () => http.get('/api/tasks/statuses'),
};

export const reportApi = {
  dashboard: (params) => http.get('/api/reports/dashboard', params),
};

export const notificationApi = {
  list: (params) => http.get('/api/notifications', params),
  unreadCount: () => http.get('/api/notifications/unread-count'),
  readAll: () => http.post('/api/notifications/read-all'),
  read: (ids) => http.post('/api/notifications/read', { ids }),
};
