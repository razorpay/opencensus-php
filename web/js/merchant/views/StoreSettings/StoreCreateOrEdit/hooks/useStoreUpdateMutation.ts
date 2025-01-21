import { useMutation } from '@tanstack/react-query';

import { graphqlRequestMutation } from 'common/services/graphql/graphql-client';
import { STORE_UPDATE } from 'merchant/views/StoreSettings/StoreCreateOrEdit/mutations';
import {
  StoreCreatePayload,
  StoreUpdateResponse,
} from 'merchant/views/StoreSettings/StoreCreateOrEdit/types';

const useStoreUpdateMutation = ({ onSuccessHandler, onErrorHandler }) => {
  const { mutate: updateStore, isLoading } = useMutation<
    StoreUpdateResponse,
    unknown,
    StoreCreatePayload
  >({
    mutationFn: (variables) =>
      graphqlRequestMutation({
        document: STORE_UPDATE,
        variables,
      }),
    onSuccess: onSuccessHandler,
    onError: onErrorHandler,
  });

  return {
    updateStore,
    isLoading,
  };
};

export default useStoreUpdateMutation;
