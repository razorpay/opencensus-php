import { useMutation } from '@tanstack/react-query';

import { graphqlRequestMutation } from 'common/services/graphql/graphql-client';
import { STORE_TERMINAL_DELETE } from 'merchant/views/StoreSettings/StoreCreateOrEdit/mutations';

const useStoreTerminalDeleteMutation = ({ onSuccessHandler, onErrorHandler }) => {
  const { mutate: deleteStoreTerminal, isLoading } = useMutation<
    {
      storeTerminalDelete: { message: string; success: boolean };
    },
    unknown,
    { id: string }
  >({
    mutationFn: (variables) =>
      graphqlRequestMutation({
        document: STORE_TERMINAL_DELETE,
        variables,
      }),
    onSuccess: onSuccessHandler,
    onError: onErrorHandler,
  });

  return {
    deleteStoreTerminal,
    isLoading,
  };
};

export default useStoreTerminalDeleteMutation;
