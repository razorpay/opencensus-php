import { UseQueryResult, useQuery } from '@tanstack/react-query';

import { ShowNotificationType } from 'common/typings';
import { merchantFetch } from 'merchant/utils/ajax';
export type ReferralData = {
  banking: {
    id: string;
    merchant_id: string;
    ref_code: string;
    url: string;
    product: string;
  };
  primary: {
    id: string;
    merchant_id: string;
    ref_code: string;
    url: string;
    easy_kyc_access_url: string;
    product: string;
  };
  capital: {
    id: string;
    merchant_id: string;
    ref_code: string;
    url: string;
    product: string;
  };
};
const useReferralLinks = ({
  showNotification,
}: {
  showNotification: ShowNotificationType;
}): UseQueryResult<ReferralData> => {
  return useQuery({
    queryKey: ['fetch-referrals'],
    queryFn: async (): Promise<ReferralData> => {
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
