import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import type { User, Tenant } from '@/types/auth';

interface AuthState {
    token: string | null;
    user: User | null;
    tenant: Tenant | null;
    setAuth: (token: string, user: User, tenant: Tenant) => void;
    logout: () => void;
}

// create global state using zustand and persist it to localstorage
export const useAuthStore = create<AuthState>()(
    persist(
        (set) => ({
            token: null,
            user: null,
            tenant: null,
            setAuth: (token, user, tenant) => set({ token, user, tenant }),
            logout: () => set({ token: null, user: null, tenant: null }),
        }),
        { name: 'pulseboard-auth' }
    )
);