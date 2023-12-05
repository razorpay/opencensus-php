import { useQuery } from '@tanstack/react-query';

import { ShowNotificationType } from 'common/typings';
import { merchantFetch } from 'merchant/utils/ajax';

const useReferralLinks = ({ showNotification }: { showNotification: ShowNotificationType }) => {
  return useQuery({
    queryKey: ['fetch-referrals'],
    queryFn: async (): Promise<{
      primary: { easy_kyc_access_url?: string; url: string };
      banking: { url: string };
      capital: { url: string };
    }> => {
      const { data } = await merchantFetch({
        url: 'merchant/referral',
        mode: 'live',
        method: 'post',
        data: {},
      });
      return data.referrals;
    },
    enabled: true,
    retry: false,
    refetchOnWindowFocus: false,
    refetchOnMount: false,
    cacheTime: 1000 * 60 * 1,
    staleTime: Infinity,
    onError: (_err) => {
      showNotification?.({
        type: 'error',
        message: 'There was an error fetching the invite links',
      });
    },
  });
};

export default useReferralLinks;
