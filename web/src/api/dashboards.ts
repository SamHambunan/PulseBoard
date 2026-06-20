import { apiClient } from './client';
import type { Dashboard } from '@/types/dashboard';

export const dashboardsApi = {
    // fetch all dashboards for the logged-in user
    list: () =>
        apiClient.get<{ data: Dashboard[] }>('/dashboards'),

    // fetch a single dashboard by id
    get: (id: string) =>
        apiClient.get<{ data: Dashboard }>(`/dashboards/${id}`),

    // create a new dashboard
    create: (data: { name: string }) =>
        apiClient.post<{ data: Dashboard }>('/dashboards', data),

    // delete a dashboard
    remove: (id: string) =>
        apiClient.delete(`/dashboards/${id}`),
};