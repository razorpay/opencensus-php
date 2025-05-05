import { useStore } from '@federated/apps/shell/commonStore';
import { graphqlRequest } from '@federated/apps/shell/graphql';
import { useQuery, UseQueryResult } from '@tanstack/react-query';
import { MERCHANT_DETAILS_QUERY } from 'apps/onboarding-experience/src/common/queries/merchant';
import { MerchantResponseType } from 'apps/onboarding-experience/src/common/types/merchant';

const useMerchant = (): UseQueryResult<MerchantResponseType> => {
  const activeUser = useStore((state) => state.session.user);

  return useQuery<MerchantResponseType>({
    refetchOnWindowFocus: false,
    queryKey: ['merchant_activation_data', activeUser.merchant?.id],
    retry: 3,
    queryFn: () =>
      graphqlRequest({
        document: MERCHANT_DETAILS_QUERY,
        variables: {
          id: activeUser.merchant?.id,
        },
      }),
  });
};

export default useMerchant;
