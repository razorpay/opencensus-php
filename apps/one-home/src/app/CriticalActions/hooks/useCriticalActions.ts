import fetchUCSData from '@apps/one-home/src/services/ucs/fetchUcsData';
import { useQuery, UseQueryResult } from '@tanstack/react-query';
import { CriticalActions } from '../types';

const useCriticalActions = () => {
  const {
    data,
    isLoading,
    error: queryError,
  }: UseQueryResult = useQuery(['one_home_critical_action'], {
    queryFn: async () => {
      try {
        const response = await fetchUCSData({
          alias: 'one_home_critical_action',
        });

        if (!response) {
          throw new Error('No data available');
        }
        return response;
      } catch (e: unknown) {
        console.warn('Failed to get data for critical section');
        throw new Error('No critical actions available');
      }
    },
    staleTime: 5 * 60 * 1000,
    cacheTime: 5 * 60 * 1000,
    refetchOnWindowFocus: false,
    retry: false,
    networkMode: 'always',
  });

  const criticalActionsData = data as CriticalActions;
  return {
    criticalActionsData,
    criticalActionDataLength: criticalActionsData?.components?.length ?? 0,
    isLoading,
    error: queryError || criticalActionsData?.error,
  };
};

export default useCriticalActions;
