import { useMutation } from '@tanstack/react-query';

import { graphqlRequestMutation } from 'common/services/graphql/graphql-client';
import { STORE_TERMINAL_CREATE } from 'merchant/views/StoreSettings/StoreCreateOrEdit/mutations';
import {
  StoreTerminalCreateResponse,
  StoreTerminalCreateVariable,
} from 'merchant/views/StoreSettings/StoreCreateOrEdit/types';

const useStoreTerminalCreateMutation = ({ onSuccessHandler, onErrorHandler }) => {
  const { mutate: createStoreTerminal, isLoading } = useMutation<
    StoreTerminalCreateResponse,
    unknown,
    StoreTerminalCreateVariable
  >({
    mutationFn: (variables) =>
      graphqlRequestMutation({
        document: STORE_TERMINAL_CREATE,
        variables,
      }),
    onSuccess: onSuccessHandler,
    onError: onErrorHandler,
  });

  return {
    createStoreTerminal,
    isLoading,
  };
};

export default useStoreTerminalCreateMutation;
