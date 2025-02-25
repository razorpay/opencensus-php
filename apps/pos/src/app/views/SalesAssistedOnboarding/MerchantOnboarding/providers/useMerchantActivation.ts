import { graphqlRequest } from '@federated/apps/shell/graphql';
import { useToast } from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';
import { MerchantDetails } from 'apps/pos/src/app/types/SalesAssistedOnboarding';
import { MERCHANT_DETAILS } from 'apps/pos/src/services/queries/SalesDashboard';

interface UseMerchantActivationProps {
  merchantId: string;
  onMerchantDetailsFetchError?: () => void;
}

interface UseMerchantActivationResponse {
  merchantDetails: MerchantDetails | undefined;
  isMerchantDetailsLoading: boolean;
}

const useMerchantActivation = ({
  merchantId,
  onMerchantDetailsFetchError,
}: UseMerchantActivationProps): UseMerchantActivationResponse => {
  const toast = useToast();
  const handleMerchantDetailsFetchError = () => {
    toast.show({
      content: 'Failed to fetch merchant details',
      color: 'negative',
      autoDismiss: true,
    });
  };

  const { data: merchantDetails, isLoading: isMerchantDetailsLoading } = useQuery({
    queryKey: ['merchantDetails', merchantId],
    queryFn: async () => {
      const response = await graphqlRequest<'merchantById', MerchantDetails, undefined>({
        document: MERCHANT_DETAILS,
        variables: {
          id: merchantId,
        },
      });

      if (response?.merchantById?.id) {
        return response?.merchantById;
      }
      onMerchantDetailsFetchError?.();
      handleMerchantDetailsFetchError();
    },
    cacheTime: 1000 * 60 * 1,
    staleTime: Infinity,
    enabled: !!merchantId,
    retry: false,
    networkMode: 'always',
    refetchOnWindowFocus: false,
    refetchOnMount: false,
  });

  return {
    merchantDetails,
    isMerchantDetailsLoading,
  };
};

export default useMerchantActivation;
