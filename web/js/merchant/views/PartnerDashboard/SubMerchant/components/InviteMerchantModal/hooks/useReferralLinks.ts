import { useQuery } from 'react-query';

import { merchantFetch } from 'merchant/utils/ajax';
import { ShowNotificationType } from 'common/typings';

const useReferralLinks = ({
  showNotification,
}: {
  showNotification: ShowNotificationType;
}): {
  isLoading: boolean;
  data: {
    primary: { easy_kyc_access_url?: string; url: string };
    banking: { url: string };
    capital: { url: string };
  };
} & ReturnType<typeof useQuery> => {
  return useQuery(
    ['fetch-referrals'],
    async () => {
      const { data } = await merchantFetch({
        url: 'merchant/referral',
        mode: 'live',
        method: 'post',
        data: {},
      });
      return data.referrals;
    },
    {
      enabled: true,
      retry: false,
      refetchOnWindowFocus: false,
      refetchOnMount: false,
      cacheTime: 1000 * 60 * 1,
      staleTime: Infinity,
      onError: (_err) => {
        showNotification?.({
          type: 'error',
          message: 'There was an error',
        });
      },
    },
  );
};

export default useReferralLinks;
