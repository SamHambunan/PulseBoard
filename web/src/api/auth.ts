import { apiClient } from './client';
import type { AuthResponse, User, Tenant } from '@/types/auth';

export interface LoginPayload {
    email: string;
    password: string;
}

// add the registration payload shape
export interface RegisterPayload {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    company_name: string;
}

export const authApi = {
    login: (data: LoginPayload) =>
        apiClient.post<AuthResponse>('/auth/login', data),

    // add the register api call
    register: (data: RegisterPayload) =>
        apiClient.post<AuthResponse>('/auth/register', data),

    logout: () =>
        apiClient.post('/auth/logout'),

    me: () =>
        apiClient.get<{ user: User; tenant: Tenant }>('/auth/me'),
};