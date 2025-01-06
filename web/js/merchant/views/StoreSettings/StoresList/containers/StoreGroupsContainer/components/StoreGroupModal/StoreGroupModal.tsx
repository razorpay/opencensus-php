import React, { useState, useEffect } from 'react';
import {
  Box,
  Modal,
  Button,
  ModalBody,
  ModalFooter,
  ModalHeader,
  TextInput,
  Divider,
  Spinner,
} from '@razorpay/blade/components';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import isEmpty from 'lodash/isEmpty';

import { graphqlRequest } from 'common/services/graphql/graphql-client';
import StoreFilter from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/components/StoreFilter';
import { storeGroupInitialState } from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/components/StoreGroupModal/constants';
import useStoreGroupCreateMutation from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/components/StoreGroupModal/hooks/useStoreGroupCreateMutation';
import useStoreGroupUpdateMutation from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/components/StoreGroupModal/hooks/useStoreGroupUpdateMutation';
import { StoreGroupModalStatus } from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/constants';
import { STORE_GROUP_DATA_QUERY } from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/queries';
import { useStoreGroupsStore } from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/stores/storeGroupsStore';
import { useStoresTablePayloadStore } from 'merchant/views/StoreSettings/StoresList/containers/StoresTableContainer/stores/storesTablePayloadStore';

import type {
  StoreGroupResponse,
  StoreGroup,
  StoreGroupInfoType,
  ModifiedFieldsMapType,
  StoreType,
} from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/types';

type StoreGroupModalProps = {
  showModal: boolean;
  title: string;
  submitBtnText: string;
};

const StoreGroupModal = ({
  showModal,
  title,
  submitBtnText,
}: StoreGroupModalProps): React.ReactElement => {
  const [storeGroupInfo, setStoreGroupInfo] = useState<StoreGroupInfoType>(storeGroupInitialState);
  const [modifiedFieldsMap, setModifiedFieldsMap] = useState<ModifiedFieldsMapType>({});

  const queryClient = useQueryClient();
  const { storesFilterPayload } = useStoresTablePayloadStore();

  const {
    modalStatus,
    updateModalStatus,
    selectedStoreGroupInfo,
    setSelectedStoreGroupInfo,
    resetStoreGroupsList,
  } = useStoreGroupsStore();

  const {
    data: storeGroupResponse,
    isFetching: isStoreGroupInfoFetching,
    refetch,
  } = useQuery<StoreGroupResponse>({
    enabled: false,
    queryKey: ['store_group_info'],
    queryFn: () =>
      graphqlRequest({
        document: STORE_GROUP_DATA_QUERY,
        variables: { id: selectedStoreGroupInfo?.id },
      }),
  });

  useEffect(() => {
    if (modalStatus === StoreGroupModalStatus.UPDATE) {
      refetch();
    } else if (modalStatus === StoreGroupModalStatus.CREATE) {
      setStoreGroupInfo(storeGroupInitialState);
    }
  }, [modalStatus]);

  useEffect(() => {
    if (!isStoreGroupInfoFetching && storeGroupResponse?.storeGroupById) {
      const fetchedStoreGroup = storeGroupResponse?.storeGroupById;
      const selectedStores = {};
      fetchedStoreGroup?.stores?.forEach((store: StoreType) => {
        const { id, name, storeInfo } = store;
        selectedStores[id] = { label: `${storeInfo.storeCode} - ${name}`, value: id };
      });
      const updatedStoreGroupInfo = {
        name: fetchedStoreGroup.name,
        description: fetchedStoreGroup.description,
        stores: selectedStores,
      };
      setStoreGroupInfo(updatedStoreGroupInfo);
    }
  }, [isStoreGroupInfoFetching, storeGroupResponse?.storeGroupById]);

  const { name, description, stores } = storeGroupInfo || {};

  const handleInfoChange = <T extends keyof StoreGroupInfoType>(
    fieldName: T,
    fieldValue: StoreGroupInfoType[T],
  ): void => {
    let updatedFieldValue = fieldValue;
    if (typeof fieldValue === 'string') {
      updatedFieldValue = fieldValue.trim() as StoreGroupInfoType[T];
      // Added type check in 'if' block again as TypeScript is throwing error for updatedFieldValue which is assigned StoreGroupInfoType[T]
      if (typeof updatedFieldValue === 'string' && updatedFieldValue.length > 0) {
        // If 'updatedFieldValue' is not empty after trimming, then update its value back to 'fieldValue'.
        // because, if user enters space between characters, then it should be considered as a valid input
        updatedFieldValue = fieldValue;
      }
    }
    setStoreGroupInfo({ ...storeGroupInfo, [fieldName]: updatedFieldValue });
    if (!modifiedFieldsMap[fieldName]) {
      setModifiedFieldsMap({ ...modifiedFieldsMap, [fieldName]: true });
    }
  };

  const handleModalClose = () => {
    setModifiedFieldsMap({});
    updateModalStatus(null);
    queryClient.removeQueries({
      queryKey: ['store_group_info'],
    });
  };

  const onSuccessHandler = (updatedStoreGroup?: StoreGroup) => {
    handleModalClose();
    resetStoreGroupsList();
    queryClient.removeQueries({
      queryKey: ['store_groups_list_data'],
    });

    queryClient.removeQueries({
      queryKey: [
        'stores_table_data',
        {
          offset: storesFilterPayload.offset,
          limit: storesFilterPayload.limit,
          selectedStoreGroupId: selectedStoreGroupInfo?.id,
        },
      ],
    });

    if (updatedStoreGroup) {
      const { id, name, description, isActive, stores } = updatedStoreGroup;
      setSelectedStoreGroupInfo({
        id,
        name,
        description,
        isActive,
        storesCount: stores.length,
      });
    }
  };

  const { createStoreGroup, isCreateStoreGroupLoading } = useStoreGroupCreateMutation({
    storeGroupInfo,
    onSuccessHandler,
  });

  const { updateStoreGroup, isUpdateStoreGroupLoading } = useStoreGroupUpdateMutation({
    storeGroupInfo,
    onSuccessHandler,
    modifiedFieldsMap,
    selectedStoreGroupInfo: selectedStoreGroupInfo!,
    storeGroupId: selectedStoreGroupInfo?.id as string,
  });

  const handleModalSubmit = () => {
    if (modalStatus === StoreGroupModalStatus.CREATE) {
      createStoreGroup();
    } else if (selectedStoreGroupInfo?.id) {
      updateStoreGroup();
    }
  };

  return (
    <Modal isOpen={showModal} onDismiss={handleModalClose} size="medium">
      <ModalHeader title={title} subtitle="Group your stores for better filtering of data." />
      <ModalBody>
        <Box height="50vh">
          {isStoreGroupInfoFetching ||
          (modalStatus === StoreGroupModalStatus.UPDATE && !storeGroupResponse?.storeGroupById) ? (
            <Box display="flex" alignItems="center" justifyContent="center" height="100%">
              <Spinner accessibilityLabel="Store Group info loading" />
            </Box>
          ) : (
            <Box display="flex" flexDirection="column" gap="spacing.4">
              <TextInput
                isRequired
                validationState={
                  modifiedFieldsMap.name && (name.length === 0 || name.length > 50)
                    ? 'error'
                    : 'none'
                }
                errorText={
                  name.length > 50
                    ? 'Store Group name cannot be more than 50 characters'
                    : 'Name is a mandatory field'
                }
                label="Group Name"
                placeholder="Enter Group Name"
                necessityIndicator="required"
                value={name}
                onChange={({ value }) => handleInfoChange('name', value || '')}
                size="large"
                labelPosition="left"
              />
              <TextInput
                label="Group Description"
                placeholder="Enter Group Description"
                value={description || ''}
                onChange={({ value }) => handleInfoChange('description', value || '')}
                size="large"
                labelPosition="left"
                maxCharacters={200}
              />
              <Divider variant="normal" thickness="thinner" />
              <StoreFilter
                selectedStores={stores}
                onSelectStores={(stores) => handleInfoChange('stores', stores)}
              />
            </Box>
          )}
        </Box>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" gap="spacing.5" justifyContent="flex-end" width="100%">
          <Button
            variant="tertiary"
            onClick={handleModalClose}
            isDisabled={
              isStoreGroupInfoFetching || isCreateStoreGroupLoading || isUpdateStoreGroupLoading
            }
          >
            Cancel
          </Button>
          <Button
            isDisabled={
              !name.length || name.length > 50 || isEmpty(stores) || isStoreGroupInfoFetching
            }
            onClick={handleModalSubmit}
            isLoading={isCreateStoreGroupLoading || isUpdateStoreGroupLoading}
          >
            {submitBtnText}
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};

export default StoreGroupModal;
