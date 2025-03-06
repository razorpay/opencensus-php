import { graphqlRequest } from 'common/services/graphql/graphql-client';
import { useQuery } from '@tanstack/react-query';
import { useToast } from '@razorpay/blade/components';
import { TERMINAL_BY_STORE_ID } from 'merchant/views/StoreSettings/StoreCreateOrEdit/queries';
import { verifyGqlErrorResponse } from 'merchant/views/BillMeSettings/common/utils';

const useStoreTerminalQuery = ({ id, currentStep }) => {
  const toast = useToast();
  const { data: storeTerminalsResponse, isFetching: isStoreTerminalsResponseFetching } = useQuery({
    queryKey: ['store-terminals', id, currentStep],
    queryFn: async () =>
      graphqlRequest({
        document: TERMINAL_BY_STORE_ID,
        variables: { storeIds: [id], offset: 0, limit: 30 },
      }),
    retry: false,
    refetchOnWindowFocus: false,
    enabled: !!id,
    onSettled: (_, error) => {
      if (error) {
        const isTerminalsAccessDenied = verifyGqlErrorResponse(error);
        toast.show({
          type: 'informational',
          color: 'negative',
          content: isTerminalsAccessDenied
            ? 'Access denied to view Store Terminals'
            : 'Something went wrong in fetching Store Terminals',
        });
      }
    },
  });

  return {
    storeTerminalsResponse,
    isStoreTerminalsResponseFetching,
  };
};

export default useStoreTerminalQuery;
