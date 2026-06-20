import { Outlet, Link, useLocation } from 'react-router-dom';
import { useAuthStore } from '@/store/auth';

export default function DashboardLayout() {
    const { user, tenant, logout } = useAuthStore();
    const location = useLocation();

    // simple helper to check if a sidebar link is active
    const isActive = (path: string) => location.pathname.startsWith(path);

    return (
        <div className="flex h-screen bg-background overflow-hidden">

            {/* sidebar (white surface, full height) */}
            <aside className="w-64 bg-surface border-r border-gray-100 flex flex-col shadow-soft z-10">

                {/* logo area */}
                <div className="h-20 flex items-center px-8 border-b border-gray-50">
                    <div className="flex items-center gap-3">
                        <div className="w-8 h-8 bg-primary rounded-lg flex items-center justify-center text-white font-bold">
                            Pb
                        </div>
                        <span className="text-xl font-bold tracking-tight text-text-main">pulseboard</span>
                    </div>
                </div>

                {/* navigation links */}
                <nav className="flex-1 p-4 space-y-1 overflow-y-auto">
                    <p className="px-4 text-xs font-semibold text-text-muted uppercase tracking-wider mb-2 mt-4">
                        menu
                    </p>

                    <Link
                        to="/dashboards"
                        className={`flex items-center gap-3 px-4 py-3 rounded-xl transition-colors ${
                            isActive('/dashboards')
                                ? 'bg-primary text-white shadow-soft shadow-primary/30'
                                : 'text-text-muted hover:bg-background hover:text-text-main'
                        }`}
                    >
                        {/* home icon svg */}
                        <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span className="font-medium">dashboards</span>
                    </Link>

                    {/* placeholder for future settings route */}
                    <button className="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-text-muted hover:bg-background hover:text-text-main transition-colors text-left">
                        <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span className="font-medium">settings</span>
                    </button>
                </nav>

                {/* user profile / logout area at the bottom */}
                <div className="p-4 border-t border-gray-50">
                    <div className="flex items-center justify-between px-4 py-2">
                        <div className="flex flex-col">
                            <span className="text-sm font-bold text-text-main">{user?.name}</span>
                            <span className="text-xs text-text-muted capitalize">{user?.workspace_role}</span>
                        </div>
                        <button onClick={logout} className="text-text-muted hover:text-accent-pinktext transition-colors" title="log out">
                            <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                <path strokeLinecap="round" strokeLinejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </button>
                    </div>
                </div>
            </aside>

            {/* main content area */}
            <div className="flex-1 flex flex-col overflow-hidden">
                {/* top header */}
                <header className="h-20 flex items-center justify-between px-8 bg-background z-0">
                    <h1 className="text-2xl font-bold text-text-main capitalize">
                        {/* dynamic title based on url */}
                        {location.pathname.split('/')[1] || 'dashboard'}
                    </h1>

                    <div className="flex items-center gap-4">
                        {/* tenant badge */}
                        <div className="px-3 py-1.5 bg-white rounded-full shadow-soft text-sm font-medium text-text-main border border-gray-100">
                            {tenant?.name}
                        </div>
                    </div>
                </header>

                {/* outlet renders the specific page (like DashboardListPage) */}
                <main className="flex-1 overflow-y-auto p-8">
                    <Outlet />
                </main>
            </div>
        </div>
    );
}