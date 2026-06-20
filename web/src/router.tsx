import { createBrowserRouter, Navigate, Outlet } from 'react-router-dom';
import { useAuthStore } from '@/store/auth';
import LoginPage from '@/pages/auth/LoginPage';
import RegisterPage from '@/pages/auth/RegisterPage';
import DashboardLayout from '@/components/layout/DashboardLayout';
import DashboardListPage from '@/pages/dashboard/DashboardListPage';
import DashboardDetailPage from '@/pages/dashboard/DashboardDetailPage';

function GuestRoute() {
    const token = useAuthStore((s) => s.token);
    if (token) return <Navigate to="/dashboards" replace />;
    return <Outlet />;
}

function ProtectedRoute() {
    const token = useAuthStore((s) => s.token);
    if (!token) return <Navigate to="/login" replace />;
    // change this from <Outlet /> to our new layout
    return <DashboardLayout />;
}

export const router = createBrowserRouter([
    {
        element: <GuestRoute />,
        children: [
            { path: '/login', element: <LoginPage /> },
            { path: '/register', element: <RegisterPage /> },
        ],
    },
    {
        element: <ProtectedRoute />,
        children: [
            // map the /dashboards route to our new grid page
            { path: '/dashboards', element: <DashboardListPage /> },
            { path: '/dashboards/:id', element: <DashboardDetailPage /> },
        ],
    },
    {
        path: '/',
        element: <Navigate to="/dashboards" replace />,
    },
]);