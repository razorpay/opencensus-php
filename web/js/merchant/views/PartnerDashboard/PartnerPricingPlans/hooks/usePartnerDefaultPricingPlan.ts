import { useQuery } from 'react-query';

import { ShowNotificationType } from 'common/typings';
import { merchantFetch } from 'merchant/utils/ajax';
import { PartnerPricingPlans } from 'merchant/views/PartnerDashboard/PartnerPricingPlans/types';

type DefaultPartnerConfigResponse = {
  data: PartnerPricingPlans | undefined;
  refetch: () => Promise<PartnerPricingPlans | undefined>;
  isLoading: boolean;
  isError: boolean;
};

export const usePartnerDefaultPricingPlan = (
  partner_id?: string | null,
  showNotification?: ShowNotificationType,
): DefaultPartnerConfigResponse => {
  const { data, isLoading, isError, refetch } = useQuery(
    'get-partner-default-pricing',
    async () => {
      const { data } = await merchantFetch({
        url: `partner_config/default`,
        method: 'get',
        data: {
          partner_id,
          expand: 'default_plan_id',
        },
      });
      return data?.default_plan_id_details;
    },
    {
      retry: false,
      refetchOnWindowFocus: false,
      refetchOnMount: false,
      cacheTime: 1000 * 60 * 1,
      staleTime: Infinity,
      onError: ({ errors }) => {
        showNotification?.({
          type: 'error',
          message: errors,
        });
      },
    },
  );
  return { data, isLoading, isError, refetch };
};
