import { useToast } from '@razorpay/blade/components';
import { useMutation, UseMutateFunction } from '@tanstack/react-query';

import { graphqlRequestMutation } from '@federated/apps/shell/graphql';
import { CREATE_STORE_GROUP_MUTATION } from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/mutations';

import type {
  StoreGroup,
  StoreGroupInfoType,
  StoreChipType,
} from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/types';

type StoreGroupCreateResponse = {
  storeGroupCreate: {
    code: number;
    success: boolean;
    message: string;
    storeGroup: StoreGroup;
  };
};

const useStoreGroupCreateMutation = ({
  storeGroupInfo,
  onSuccessHandler,
}: {
  storeGroupInfo: StoreGroupInfoType;
  onSuccessHandler: () => void;
}): {
  createStoreGroup: UseMutateFunction<StoreGroupCreateResponse>;
  isCreateStoreGroupLoading: boolean;
} => {
  const toast = useToast();
  const { mutate: createStoreGroup, isLoading: isCreateStoreGroupLoading } =
    useMutation<StoreGroupCreateResponse>({
      mutationFn: async () => {
        const response = await graphqlRequestMutation({
          document: CREATE_STORE_GROUP_MUTATION,
          variables: {
            ...storeGroupInfo,
            name: storeGroupInfo?.name?.trim(),
            description: storeGroupInfo?.description?.trim(),
            stores: Object.values(storeGroupInfo?.stores || []).map(
              (store: StoreChipType) => store?.value,
            ),
          },
        });
        return response;
      },
      onSettled: (response) => {
        if (!response?.storeGroupCreate?.success) {
          toast.show({
            type: 'informational',
            color: 'negative',
            content: response?.storeGroupCreate?.message || 'Failed to create Store Group',
          });
        } else {
          toast.show({
            type: 'informational',
            color: 'positive',
            content: 'Store Group created successfully',
          });
          onSuccessHandler();
        }
      },
    });

  return { createStoreGroup, isCreateStoreGroupLoading };
};

export default useStoreGroupCreateMutation;
