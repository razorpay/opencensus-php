import { useQuery } from '@tanstack/react-query';

import { ShowNotificationType } from 'common/typings';
import { merchantFetch } from 'merchant/utils/ajax';

interface TApplication {
  id: string;
  name: string;
  logo_url: string;
  created_at: number;
  client_details: {
    prod: {
      id: string;
      redirect_url: string[];
    };
  };
}
type FetchApplicationResult = Array<TApplication>;

const useFetchApplications = ({ showNotification }: { showNotification: ShowNotificationType }) => {
  return useQuery({
    queryKey: ['fetch-applications'],
    queryFn: async (): Promise<FetchApplicationResult> => {
      const { data } = await merchantFetch({
        url: 'oauth/applications',
        mode: 'live',
        method: 'get',
        data: {},
      });
      return data.items;
    },
    enabled: true,
    retry: false,
    refetchOnWindowFocus: true,
    refetchOnMount: true,
    cacheTime: 1000 * 60 * 1,
    staleTime: Infinity,
    onError: (_err) => {
      showNotification?.({
        type: 'error',
        message: 'There was an error',
      });
    },
  });
};

export default useFetchApplications;
