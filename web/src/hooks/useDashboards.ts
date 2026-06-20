import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { dashboardsApi } from '@/api/dashboards';

// hook to fetch the list of dashboards
export function useDashboards() {
    return useQuery({
        queryKey: ['dashboards'],
        queryFn: () => dashboardsApi.list().then((res) => res.data.data),
    });
}

// hook to create a dashboard
export function useCreateDashboard() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: dashboardsApi.create,
        onSuccess: () => {
            // tells react query to refetch the dashboard list immediately after creating one
            queryClient.invalidateQueries({ queryKey: ['dashboards'] });
        },
    });

}

export function useDashboard(id: string) {
    return useQuery({
        // the query key includes the id so react-query caches each dashboard separately
        queryKey: ['dashboard', id],
        queryFn: () => dashboardsApi.get(id).then((res) => res.data.data),
        // don't try to fetch if we don't have an id yet
        enabled: !!id,
    });
}