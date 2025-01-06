import { useToast } from '@razorpay/blade/components';
import { useMutation, UseMutateFunction } from '@tanstack/react-query';

import { graphqlRequestMutation } from 'common/services/graphql/graphql-client';
import { UPDATE_STORE_GROUP_MUTATION } from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/mutations';

import type {
  StoreGroup,
  StoreGroupForListing,
  StoreGroupInfoType,
  ModifiedFieldsMapType,
  StoreChipType,
} from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/types';

type StoreGroupUpdateResponse = {
  storeGroupUpdate: {
    code: number;
    success: boolean;
    message: string;
    storeGroup: StoreGroup;
  };
};

const useStoreGroupUpdateMutation = ({
  storeGroupId,
  storeGroupInfo,
  onSuccessHandler,
  modifiedFieldsMap,
  selectedStoreGroupInfo,
}: {
  storeGroupId: string;
  storeGroupInfo: StoreGroupInfoType;
  onSuccessHandler: (storeGroup: StoreGroup) => void;
  modifiedFieldsMap: ModifiedFieldsMapType;
  selectedStoreGroupInfo: StoreGroupForListing;
}): {
  updateStoreGroup: UseMutateFunction<StoreGroupUpdateResponse>;
  isUpdateStoreGroupLoading: boolean;
} => {
  const toast = useToast();
  const { mutate: updateStoreGroup, isLoading: isUpdateStoreGroupLoading } =
    useMutation<StoreGroupUpdateResponse>({
      mutationFn: async () => {
        const modifiedFields = Object.keys(modifiedFieldsMap);
        const variables = {
          id: storeGroupId,
        };
        modifiedFields.forEach((field) => {
          if (field === 'stores') {
            variables[field] = Object.values(storeGroupInfo?.stores || []).map(
              (store: StoreChipType) => store?.value,
            );
          } else if (
            field !== 'name' ||
            (field === 'name' && storeGroupInfo[field] !== selectedStoreGroupInfo[field])
          ) {
            // If 'name' field value is re-entered same as API response, then it should be skipped in the mutation payload
            if (typeof storeGroupInfo[field] === 'string') {
              variables[field] = storeGroupInfo[field].trim();
            } else {
              variables[field] = storeGroupInfo[field];
            }
          }
        });
        const response = await graphqlRequestMutation({
          document: UPDATE_STORE_GROUP_MUTATION,
          variables,
        });
        return response;
      },
      onSettled: (response) => {
        if (!response?.storeGroupUpdate?.success) {
          toast.show({
            type: 'informational',
            color: 'negative',
            content: response?.storeGroupUpdate?.message || 'Failed to update Store Group',
          });
        } else {
          toast.show({
            type: 'informational',
            color: 'positive',
            content: 'Store Group updated successfully',
          });
          onSuccessHandler(response?.storeGroupUpdate?.storeGroup);
        }
      },
    });

  return { updateStoreGroup, isUpdateStoreGroupLoading };
};

export default useStoreGroupUpdateMutation;
