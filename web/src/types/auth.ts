// matches backend users table + workspace_role enum exactly
export type WorkspaceRole = 'owner' | 'admin' | 'member' | 'viewer';

export interface User {
    id: string;
    tenant_id: string;
    name: string;
    email: string;
    workspace_role: WorkspaceRole;
    avatar_url?: string;
}

export interface Tenant {
    id: string;
    name: string;
    slug: string;
    plan: 'free' | 'pro' | 'enterprise';
}

export interface AuthResponse {
    token: string;
    user: User;
    tenant: Tenant;
}