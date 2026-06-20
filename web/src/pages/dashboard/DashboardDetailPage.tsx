import { useParams, useNavigate } from 'react-router-dom';
import { useDashboard } from '@/hooks/useDashboards';

export default function DashboardDetailPage() {
    // grab the id from the url (e.g., /dashboards/123)
    const { id } = useParams<{ id: string }>();
    const navigate = useNavigate();

    // fetch the dashboard data
    const { data: dashboard, isLoading } = useDashboard(id!);

    if (isLoading) {
        return (
            <div className="flex items-center justify-center h-full text-text-muted">
                <div className="w-6 h-6 border-2 border-primary border-t-transparent rounded-full animate-spin mr-3"></div>
                loading dashboard...
            </div>
        );
    }

    if (!dashboard) {
        return (
            <div className="text-center mt-20">
                <h2 className="text-2xl font-bold text-text-main mb-2">dashboard not found</h2>
                <button onClick={() => navigate('/dashboards')} className="text-primary hover:underline">
                    &larr; back to all dashboards
                </button>
            </div>
        );
    }

    return (
        <div className="flex flex-col h-full">
            {/* dashboard header area */}
            <div className="flex items-center justify-between mb-8">
                <div>
                    <button
                        onClick={() => navigate('/dashboards')}
                        className="text-text-muted hover:text-text-main text-sm font-medium mb-2 flex items-center gap-1 transition-colors"
                    >
                        <span>&larr;</span> back
                    </button>
                    <h2 className="text-3xl font-bold text-text-main">{dashboard.name}</h2>
                    <p className="text-text-muted mt-1 text-sm">
                        created on {new Date(dashboard.created_at).toLocaleDateString()}
                    </p>
                </div>

                {/* action buttons (we will wire these up later) */}
                <div className="flex gap-3">
                    <button className="px-4 py-2 bg-surface text-text-main border border-gray-200 rounded-xl font-medium shadow-sm hover:bg-gray-50 transition-colors">
                        settings
                    </button>
                    <button className="px-4 py-2 bg-primary text-white rounded-xl font-medium shadow-soft shadow-primary/30 hover:bg-primary/90 transition-colors flex items-center gap-2">
                        <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        add widget
                    </button>
                </div>
            </div>

            {/* canvas area where widgets will go */}
            <div className="flex-1 bg-surface border border-gray-100 rounded-2xl shadow-soft p-8 flex items-center justify-center border-dashed border-2 bg-transparent">
                <div className="text-center max-w-sm">
                    <div className="w-16 h-16 bg-white rounded-2xl shadow-sm flex items-center justify-center mx-auto mb-4 text-primary">
                        <svg className="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <h3 className="text-lg font-bold text-text-main mb-2">this dashboard is empty</h3>
                    <p className="text-text-muted text-sm leading-relaxed mb-6">
                        start visualizing your data by adding your first widget. you can resize and drag them around perfectly.
                    </p>
                    <button className="text-primary font-semibold hover:underline">
                        + add your first widget
                    </button>
                </div>
            </div>
        </div>
    );
}