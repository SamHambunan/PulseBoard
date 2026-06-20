import { useState } from 'react';
import { useDashboards, useCreateDashboard } from '@/hooks/useDashboards';

export default function DashboardListPage() {
    const { data: dashboards, isLoading } = useDashboards();
    const createMutation = useCreateDashboard();
    const [isCreating, setIsCreating] = useState(false);

    const handleCreate = () => {
        // just a quick prompt for now, we will build a real modal later
        const name = window.prompt('enter new dashboard name:');
        if (name) {
            setIsCreating(true);
            createMutation.mutate(
                { name },
                { onSettled: () => setIsCreating(false) }
            );
        }
    };

    if (isLoading) {
        return <div className="text-text-muted flex items-center gap-2">
            <div className="w-5 h-5 border-2 border-primary border-t-transparent rounded-full animate-spin"></div>
            loading dashboards...
        </div>;
    }

    return (
        <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">

            {/* render existing dashboards */}
            {dashboards?.map((dashboard) => (
                <div key={dashboard.id} className="card group cursor-pointer hover:-translate-y-1 transition-all duration-300">
                    <div className="flex justify-between items-start mb-4">
                        <div className="w-10 h-10 rounded-xl bg-accent-purple/50 flex items-center justify-center text-accent-purpletext">
                            <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                <path strokeLinecap="round" strokeLinejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        {/* status badge */}
                        <span className={`px-2.5 py-1 text-xs font-bold rounded-full ${dashboard.is_public ? 'bg-accent-green text-accent-greentext' : 'bg-gray-100 text-text-muted'}`}>
              {dashboard.is_public ? 'public' : 'private'}
            </span>
                    </div>
                    <h3 className="text-lg font-bold text-text-main mb-1">{dashboard.name}</h3>
                    <p className="text-sm text-text-muted mb-4">
                        created {new Date(dashboard.created_at).toLocaleDateString()}
                    </p>
                    <div className="h-1 w-full bg-gray-100 rounded-full overflow-hidden">
                        {/* decorative progress bar like the reference image */}
                        <div className="h-full bg-primary rounded-full w-1/3"></div>
                    </div>
                </div>
            ))}

            {/* create new dashboard card */}
            <button
                onClick={handleCreate}
                disabled={isCreating}
                className="card border-2 border-dashed border-gray-200 bg-transparent hover:bg-white hover:border-primary/50 transition-all flex flex-col items-center justify-center min-h-[200px] text-text-muted hover:text-primary group"
            >
                <div className="w-12 h-12 rounded-full bg-gray-50 group-hover:bg-primary/10 flex items-center justify-center mb-3 transition-colors">
                    <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                        <path strokeLinecap="round" strokeLinejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                </div>
                <span className="font-semibold">{isCreating ? 'creating...' : 'new dashboard'}</span>
            </button>

        </div>
    );
}