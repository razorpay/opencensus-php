import { useStore } from '@federated/apps/shell/commonStore';
import { graphqlRequest, graphqlRequestMutation } from '@federated/apps/shell/graphql';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
  MERCHANT_PAYMENT_HANDLE_QUERY,
  MERCHANT_PAYMENT_HANDLE_ENCRYPTED_AMOUNT_MUTATION,
  MERCHANT_PAYMENT_HANDLE_SUGGESTION_QUERY,
  MERCHANT_PAYMENT_HANDLE_AVAILABILITY_QUERY,
  MERCHANT_PAYMENT_HANDLE_UPDATE_MUTATION,
} from '@OnboardingExperienceCommons/queries/paymentHandle';
import {
  addPaymentHandleSlugPrefix,
  toLowestAmountDenomination,
} from '@OnboardingExperienceCommons/utils/paymentHandle';
import {
  MerchantPaymentHandleResponseType,
  MerchantPaymentHandleSuggestionResponseType,
  MerchantPaymentHandleEncryptedAmountResponseType,
  UseMerchantPaymentHandleReturn,
  MoneyInput,
  MerchantPaymentHandleUpdateResponseType,
  MerchantPaymentHandleAvailabilityResponseType,
} from '@OnboardingExperienceCommons/types/paymentHandle';

const useMerchantPaymentHandle = (): UseMerchantPaymentHandleReturn => {
  const activeUser = useStore((state) => state.session.user);
  const queryClient = useQueryClient();

  // Fetch the merchant's payment handle
  const {
    data: paymentHandleData,
    isLoading: isPaymentHandleLoading,
    refetch: fetchPaymentHandle,
  } = useQuery<MerchantPaymentHandleResponseType>({
    refetchOnWindowFocus: false,
    queryKey: ['merchant_payment_handle', activeUser.merchant?.id],
    retry: 3,
    queryFn: () =>
      graphqlRequest({
        document: MERCHANT_PAYMENT_HANDLE_QUERY,
        variables: {
          id: activeUser.merchant?.id,
        },
      }),
    enabled: false,
  });

  // Get payment handle suggestions
  const fetchHandleSuggestions = (
    count = 4,
  ): Promise<MerchantPaymentHandleSuggestionResponseType> => {
    return graphqlRequest({
      document: MERCHANT_PAYMENT_HANDLE_SUGGESTION_QUERY,
      variables: {
        suggestionsCountInput: count,
      },
    });
  };

  // Custom refetch function to allow passing a handle parameter
  const fetchHandleAvailability = (
    handle: string,
  ): Promise<MerchantPaymentHandleAvailabilityResponseType> => {
    return graphqlRequest({
      document: MERCHANT_PAYMENT_HANDLE_AVAILABILITY_QUERY,
      variables: {
        paymentHandleSlug: addPaymentHandleSlugPrefix(handle),
      },
    });
  };

  // Mutation to encrypt an amount for payment handle
  const { mutateAsync: mutateEncryptedAmount } = useMutation<
    MerchantPaymentHandleEncryptedAmountResponseType,
    unknown,
    { amount: string }
  >({
    //@ts-ignore
    mutationFn: (variables) =>
      graphqlRequestMutation({
        document: MERCHANT_PAYMENT_HANDLE_ENCRYPTED_AMOUNT_MUTATION,
        variables: {
          amount: {
            value: toLowestAmountDenomination(parseFloat(variables.amount)),
            currency: { code: 'INR' },
          } as MoneyInput,
        },
      }),
  });

  // Mutation to update payment handle
  const { mutateAsync: updatePaymentHandle } = useMutation<
    MerchantPaymentHandleUpdateResponseType,
    unknown,
    { handle: string }
  >({
    //@ts-ignore
    mutationFn: ({ handle }) =>
      graphqlRequestMutation({
        document: MERCHANT_PAYMENT_HANDLE_UPDATE_MUTATION,
        variables: {
          paymentHandleSlug: addPaymentHandleSlugPrefix(handle),
        },
      }),
    onSuccess: ({ merchantPaymentHandleUpdate }) => {
      if (merchantPaymentHandleUpdate?.success && merchantPaymentHandleUpdate?.paymentHandle) {
        queryClient.setQueryData(['merchant_payment_handle', activeUser.merchant?.id], {
          merchantPaymentHandle: merchantPaymentHandleUpdate,
        });
      }
    },
  });

  return {
    // Payment handle data and states
    paymentHandleData,
    isPaymentHandleLoading,
    fetchPaymentHandle,

    // Handle Suggestions data
    fetchHandleSuggestions,

    // Handle availability data
    fetchHandleAvailability,

    // Mutations
    mutateEncryptedAmount,
    updatePaymentHandle,
  };
};

export default useMerchantPaymentHandle;
