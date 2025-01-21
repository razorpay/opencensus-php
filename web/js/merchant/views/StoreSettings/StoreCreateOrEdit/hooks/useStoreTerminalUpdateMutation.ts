import { useMutation } from '@tanstack/react-query';

import { graphqlRequestMutation } from 'common/services/graphql/graphql-client';
import { STORE_TERMINAL_UPDATE } from 'merchant/views/StoreSettings/StoreCreateOrEdit/mutations';

type StoreTerminalUpdateVariable = {
  id: string;
  storeId: string;
  name?: string;
  macAddress?: string;
  ipAddress?: string;
  isActive?: boolean;
};

type StoreTerminalUpdateResponse = {
  storeTerminalUpdate: {
    success: boolean;
    message: string;
    storeTerminal: {
      id: string;
      name: string;
      isActive: boolean;
      terminalInfo: {
        ipAddress: string;
        macAddress: string;
        licenseKey: string;
      };
    };
  };
};

const useStoreTerminalUpdateMutation = ({ onSuccessHandler, onErrorHandler }) => {
  const { mutate: updateStoreTerminal, isLoading } = useMutation<
    StoreTerminalUpdateResponse,
    unknown,
    StoreTerminalUpdateVariable
  >({
    mutationFn: (variables) =>
      graphqlRequestMutation({
        document: STORE_TERMINAL_UPDATE,
        variables,
      }),
    onSuccess: onSuccessHandler,
    onError: onErrorHandler,
  });

  return {
    updateStoreTerminal,
    isLoading,
  };
};

export default useStoreTerminalUpdateMutation;
