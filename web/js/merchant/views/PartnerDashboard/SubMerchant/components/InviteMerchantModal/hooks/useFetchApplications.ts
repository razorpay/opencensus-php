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
type ParsedApplicationResult = Array<
  TApplication & {
    hasInvalidAppSetting: boolean;
  }
>;
const parseApplications = (applications: FetchApplicationResult = []): ParsedApplicationResult => {
  const parsedApplications = applications.map((application) => ({
    ...application,
    hasInvalidAppSetting: application.client_details?.prod?.redirect_url?.length === 0,
  }));
  // TODO v2: move previous selections to the top (load from local storage)
  const orderedApplications = parsedApplications.sort((a, b) => {
    if (a.hasInvalidAppSetting) return 1;
    if (b.hasInvalidAppSetting) return -1;
    return a.created_at - b.created_at;
  });
  return orderedApplications;
};

const useFetchApplications = ({ showNotification }: { showNotification: ShowNotificationType }) => {
  return useQuery({
    queryKey: ['fetch-applications'],
    queryFn: async (): Promise<ParsedApplicationResult> => {
      const { data } = await merchantFetch({
        url: 'oauth/applications',
        mode: 'live',
        method: 'get',
        data: {},
      });
      return parseApplications(data.items);
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
