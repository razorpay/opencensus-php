import React from 'react';
import {
  Box,
  Modal,
  Button,
  ModalBody,
  ModalFooter,
  ModalHeader,
  Text,
  useToast,
} from '@razorpay/blade/components';
import { useMutation, useQueryClient } from '@tanstack/react-query';

import { graphqlRequestMutation } from '@federated/apps/shell/graphql';
import { StoreGroupModalStatus } from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/constants';
import { DELETE_STORE_GROUP_MUTATION } from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/mutations';
import { useStoreGroupsStore } from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/stores/storeGroupsStore';
import { useStoresTablePayloadStore } from 'merchant/views/StoreSettings/StoresList/containers/StoresTableContainer/stores/storesTablePayloadStore';

const DeleteModal = (): React.ReactElement => {
  const toast = useToast();
  const queryClient = useQueryClient();
  const {
    modalStatus,
    updateModalStatus,
    selectedStoreGroupInfo,
    setSelectedStoreGroupInfo,
    resetStoreGroupsList,
  } = useStoreGroupsStore();
  const { storesFilterPayload } = useStoresTablePayloadStore();

  const handleModalClose = () => {
    updateModalStatus(null);
  };

  const { mutate: deleteStoreGroup, isLoading } = useMutation({
    mutationFn: async () => {
      const response = await graphqlRequestMutation({
        document: DELETE_STORE_GROUP_MUTATION,
        variables: { id: selectedStoreGroupInfo?.id },
      });
      return response;
    },
    onSettled: (response) => {
      if (!response?.storeGroupDelete?.success) {
        return toast.show({
          type: 'informational',
          color: 'negative',
          content: 'Something went wrong!',
        });
      }
      toast.show({
        type: 'informational',
        color: 'positive',
        content: 'Store Group deleted successfully',
      });
      // On successful deletion, 'selectedStoreGroupInfo' and 'storeGroupsList' are reset, in order to fetch the latest list with offset 0
      handleModalClose();
      resetStoreGroupsList();
      setSelectedStoreGroupInfo(null);
      queryClient.invalidateQueries({
        queryKey: ['store_groups_list_data'],
      });
      queryClient.invalidateQueries({
        queryKey: [
          'stores_table_data',
          {
            offset: storesFilterPayload.offset,
            limit: storesFilterPayload.limit,
            selectedStoreGroupId: selectedStoreGroupInfo?.id,
          },
        ],
      });
    },
  });

  return (
    <Modal isOpen={modalStatus === StoreGroupModalStatus.DELETE} onDismiss={handleModalClose}>
      <ModalHeader title="Alert" />
      <ModalBody>
        <Box display="flex" gap="spacing.3" flexDirection="column">
          <Text>Group once deleted cannot be recovered.</Text>
          <Text>Note: The store data will not be deleted.</Text>
          <Text>Are you sure you want to delete this group?</Text>
        </Box>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
          <Button
            testID="dismiss-btn"
            variant="tertiary"
            onClick={handleModalClose}
            isDisabled={isLoading}
          >
            Cancel
          </Button>
          <Button color="negative" onClick={() => deleteStoreGroup()} isLoading={isLoading}>
            Delete
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};
export default DeleteModal;
