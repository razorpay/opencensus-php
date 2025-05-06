import { useQuery } from '@tanstack/react-query';
import fetchUCSData from '../../services/ucs/fetchUcsData';
import { QuickActionItem, QuickActionEntityAlias } from './types';

const useQuickActions = (entity: QuickActionEntityAlias) => {
  return useQuery(['quick_actions', entity], {
    queryFn: async (): Promise<QuickActionItem> => {
      try {
        const response = await fetchUCSData({
          alias: entity,
        });

        if (!response) {
          throw new Error('No Quick Actions data recieved');
        }
        return response as QuickActionItem;
      } catch (e) {
        console.warn('Failed to fetch');
        throw new Error('No Quick Actions data recieved');
      }
    },
    staleTime: 10 * 60 * 1000, // 10 mins
    cacheTime: 15 * 60 * 1000, // 15 mins
    refetchOnWindowFocus: false,
    retry: false,
    networkMode: 'always',
    retryOnMount: false,
  });
};

export default useQuickActions;
