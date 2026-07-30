import { client } from './client';

export const documentsApi = {
  uploadDocument: (type, file) => {
    const formData = new FormData();
    formData.append('type', type);
    formData.append('file', file);
    return client.upload('/nanny/documents', formData);
  },
  deleteDocument: (id) => client.delete(`/nanny/documents/${id}`),
};
