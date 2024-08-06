import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useToast } from '@razorpay/blade/components';
import { graphqlRequest } from '@dashboard/shared-utils/graphql/graphql';
import {
  MerchantModularOnboardingDetailsFailureResponse,
  MerchantModularOnboardingDetailsSuccessResponse,
} from 'apps/pos/src/app/types/modular';
import {
  MODULAR_CONFIG,
  UPDATE_MODULAR_CONFIG,
} from 'apps/pos/src/services/queries/SalesDashboard';
import { MODULAR_DEVICE_FIELDS } from 'apps/pos/src/app/types/DeviceSelection';

interface UseModular {
  modularConfig: MerchantModularOnboardingDetailsSuccessResponse | null;
  updateModularConfig: (args: Record<string, unknown>) => void;
  isUpdateModularLoading: boolean;
  isModularLoading: boolean;
  isModularFetchError: boolean;
  isModularUpdateError: boolean;
}
interface UseModularArgs {
  merchantId: string;
  onModularConfigUpdate?: (data: MerchantModularOnboardingDetailsSuccessResponse | null) => void;
}

const useModular = ({ merchantId, onModularConfigUpdate }: UseModularArgs): UseModular => {
  const toast = useToast();
  const queryClient = useQueryClient();

  const handleModularFetchError = () => {
    toast.show({
      content: 'Failed to fetch modular config',
      color: 'negative',
    });
  };

  const handleModularUpdateError = () => {
    toast.show({
      content: 'Failed to update modular config',
      color: 'negative',
    });
  };

  const {
    data: modularData,
    isLoading: isModularLoading,
    isError: isModularFetchError,
  } = useQuery<MerchantModularOnboardingDetailsSuccessResponse | null>(
    ['modularConfig', merchantId],
    async () => {
      const response = await graphqlRequest<
        'merchantModularOnboardingDetailsAsSales',
        MerchantModularOnboardingDetailsSuccessResponse,
        MerchantModularOnboardingDetailsFailureResponse
      >({
        document: MODULAR_CONFIG,
        variables: {
          merchantId,
        },
      });

      if (
        response.merchantModularOnboardingDetailsAsSales.__typename ===
        'merchantModularOnboardingDetailsSuccessResponse'
      ) {
        return response.merchantModularOnboardingDetailsAsSales as MerchantModularOnboardingDetailsSuccessResponse;
      }

      handleModularFetchError();
      return null;
    },
    {
      cacheTime: 1000 * 60 * 1,
      staleTime: Infinity,
      enabled: !!merchantId,
      retry: false,
      networkMode: 'always',
      refetchOnWindowFocus: false,
      refetchOnMount: false,
      onError: () => {
        handleModularFetchError();
      },
    },
  );

  const {
    mutate,
    isLoading: isUpdateModularLoading,
    isError: isModularUpdateError,
  } = useMutation<
    MerchantModularOnboardingDetailsSuccessResponse | null,
    MerchantModularOnboardingDetailsFailureResponse,
    Record<MODULAR_DEVICE_FIELDS, unknown>
  >({
    mutationFn: async (variables) => {
      const callbackFn = variables?.modular_callback;
      if (callbackFn) {
        delete variables.modular_callback;
      }
      const response = await graphqlRequest<
        'merchantModularOnboardingDetailsUpdateAsSales',
        MerchantModularOnboardingDetailsSuccessResponse,
        MerchantModularOnboardingDetailsFailureResponse
      >({
        document: UPDATE_MODULAR_CONFIG,
        variables: {
          merchantId,
          fieldData: variables,
        },
      });

      if (
        response.merchantModularOnboardingDetailsUpdateAsSales.__typename ===
        'merchantModularOnboardingDetailsSuccessResponse'
      ) {
        const data =
          response.merchantModularOnboardingDetailsUpdateAsSales as MerchantModularOnboardingDetailsSuccessResponse;

        if (typeof callbackFn === 'function') {
          callbackFn?.(data);
        }
        return data;
      }

      handleModularUpdateError();
      return null;
    },
    retry: false,
    onSuccess: (data) => {
      if (data) {
        queryClient.setQueryData(['modularConfig', merchantId], data);
        onModularConfigUpdate?.(data);
      }
    },
    onError: () => {
      handleModularUpdateError();
    },
  });

  const handleUpdateModularConfig = (args: Record<MODULAR_DEVICE_FIELDS, unknown>) => {
    mutate(args);
  };

  return {
    modularConfig: modularData ?? null,
    updateModularConfig: handleUpdateModularConfig,
    isModularLoading: isModularLoading && !!merchantId,
    isUpdateModularLoading,
    isModularFetchError,
    isModularUpdateError,
  };
};

export default useModular;
