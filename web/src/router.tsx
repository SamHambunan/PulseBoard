import { createBrowserRouter, Navigate, Outlet } from 'react-router-dom';
import { useAuthStore } from '@/store/auth';
import LoginPage from '@/pages/auth/LoginPage';
import RegisterPage from '@/pages/auth/RegisterPage';

// guest routes are for pages like login/register.
// if a logged-in user tries to visit them, they get redirected to the app.
function GuestRoute() {
    const token = useAuthStore((s) => s.token);
    if (token) {
        return <Navigate to="/dashboards" replace />;
    }
    return <Outlet />;
}

// protected routes are for the actual application.
// if a logged-out user tries to visit them, they get kicked to the login page.
function ProtectedRoute() {
    const token = useAuthStore((s) => s.token);
    if (!token) {
        return <Navigate to="/login" replace />;
    }
    return <Outlet />;
}

// a temporary dummy dashboard page just so our router has somewhere to go after login
function DummyDashboard() {
    const logout = useAuthStore((s) => s.logout);
    return (
        <div className="p-8">
            <h1 className="text-2xl font-bold text-text-main mb-4">dashboards</h1>
            <button onClick={logout} className="px-4 py-2 bg-text-muted text-white rounded-xl">
                log out
            </button>
        </div>
    );
}

// configure the actual browser routes
export const router = createBrowserRouter([
    {
        element: <GuestRoute />,
        children: [
            { path: '/login', element: <LoginPage /> },
            { path: '/register', element: <RegisterPage /> }
        ],
    },
    {
        element: <ProtectedRoute />,
        children: [
            { path: '/dashboards', element: <DummyDashboard /> },
            // tba /dashboards/:id here
        ],
    },
    {
        // redirect the root url to dashboards (which will auto-redirect to login if needed)
        path: '/',
        element: <Navigate to="/dashboards" replace />,
    },
]);