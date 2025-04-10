import React, { useEffect, useRef, useMemo } from 'react';
import { Box, Text, Button, PlusIcon, Spinner } from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';

import { REACT_QUERY_CACHE_KEYS } from 'common/constant';
import { graphqlRequest } from '@federated/apps/shell/graphql';
import { useIntersectionObserver } from 'merchant/hooks/useIntersectionObserver';
import StoreGroupCard from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/components/StoreGroupCard';
import StoreGroupModal from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/components/StoreGroupModal';
import { STORE_GROUPS_DATA_QUERY } from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/queries';
import { useStoreGroupsStore } from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/stores/storeGroupsStore';
import {
  STORE_GROUPS_LIST_LIMIT,
  StoreGroupModalStatus,
} from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/constants';
import { DEFAULT_STORE_GROUPS } from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/constants';
import { STORES_DATA_QUERY } from 'merchant/views/StoreSettings/StoresList/containers/StoresTableContainer/queries';
import { useStoresTablePayloadStore } from 'merchant/views/StoreSettings/StoresList/containers/StoresTableContainer/stores/storesTablePayloadStore';
import RetryOnError from 'merchant/views/BillMeSettings/common/components/RetryOnError';
import { verifyGqlErrorResponse } from 'merchant/views/BillMeSettings/common/utils';

import type {
  StoreGroupsDataResponse,
  StoreGroupForListing,
} from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/types';
import type { StoresDataResponse } from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/components/StoreFilter/types';

type StoreGroupsContainerProps = {
  isAccordionExpanded: boolean;
};

const StoreGroupsContainer = ({
  isAccordionExpanded,
}: StoreGroupsContainerProps): React.ReactElement => {
  const storeGroupsListRef = useRef(null);
  const isOnScreen = useIntersectionObserver(storeGroupsListRef);
  const {
    modalStatus,
    updateModalStatus,
    selectedStoreGroupInfo,
    setSelectedStoreGroupInfo,
    storeGroupsList,
    updateStoreGroupsList,
    totalCount,
    updateDefaultDeletedStoresGroup,
  } = useStoreGroupsStore();
  const { storesFilterPayload, setStoresFilterOffset } = useStoresTablePayloadStore();

  const { data: deletedStoresResponse, isFetching: isDeletedStoresListFetching } =
    useQuery<StoresDataResponse>({
      refetchOnWindowFocus: false,
      queryKey: [REACT_QUERY_CACHE_KEYS.DELETED_STORES_DATA],
      retry: false,
      queryFn: () =>
        graphqlRequest({
          document: STORES_DATA_QUERY,
          variables: {
            offset: 0,
            limit: 1,
            storeGroupId: null,
            isDeleted: true,
          },
        }),
    });

  const {
    data: storeGroupsResponse,
    isFetching,
    refetch,
    isError,
    error: storeGroupsListError,
  } = useQuery<StoreGroupsDataResponse>({
    enabled: true,
    refetchOnWindowFocus: false,
    retry: false,
    queryKey: ['store_groups_list_data'],
    queryFn: () =>
      graphqlRequest({
        document: STORE_GROUPS_DATA_QUERY,
        // Here, 'storeGroupsList.length - 2' is to avoid the 2 default Store Groups added from FE side
        variables: { limit: STORE_GROUPS_LIST_LIMIT, offset: storeGroupsList.length - 2 },
      }),
  });

  const storeGroupsData = storeGroupsResponse?.storeGroups?.storeGroups ?? [];
  const fetchedStoreGroupsCount = storeGroupsList.length - 2;

  const deletedStoresCount = useMemo(
    () => deletedStoresResponse?.stores?.total ?? 0,
    [deletedStoresResponse],
  );

  useEffect(() => {
    if (!isFetching && storeGroupsData.length > 0) {
      const updatedStoreGroupsData = storeGroupsData.map((storeGroup) => {
        const { id, name, description, isActive, stores } = storeGroup;
        return {
          id,
          name,
          description,
          isActive,
          storesCount: stores?.length,
        };
      });
      updateStoreGroupsList(updatedStoreGroupsData, storeGroupsResponse?.storeGroups?.total || 0);
    }
  }, [storeGroupsData]);

  // Infinite scrolling
  useEffect(() => {
    if (!isFetching && isOnScreen && fetchedStoreGroupsCount < totalCount) {
      refetch();
    }
  }, [isFetching, isOnScreen, fetchedStoreGroupsCount, totalCount]);

  useEffect(() => {
    // Updating 'selectedStoreGroupInfo' only when it is null i.e on accordion expand and on selected store group deletion
    // On create and update scenarios, selectedStoreGroupInfo won't be made null
    if (!selectedStoreGroupInfo && isAccordionExpanded) {
      setSelectedStoreGroupInfo(storeGroupsList[0]);
    }
  }, [selectedStoreGroupInfo, isAccordionExpanded]);

  useEffect(() => {
    updateDefaultDeletedStoresGroup({
      ...DEFAULT_STORE_GROUPS[1],
      storesCount: deletedStoresCount,
    });
  }, [deletedStoresCount]);

  const renderLoader = (accessibilityLabel: string) => (
    <Box display="flex" alignItems="center" justifyContent="center" height="100%">
      <Spinner accessibilityLabel={accessibilityLabel} />
    </Box>
  );

  if (isError) {
    const isStoreGroupsAccessDenied = verifyGqlErrorResponse(storeGroupsListError);
    return (
      <RetryOnError
        errorText="Error in fetching Store Groups list"
        retryFn={!isStoreGroupsAccessDenied ? refetch : undefined}
      />
    );
  }

  return (
    <>
      <Text weight="medium" size="medium">
        Create custom store groups in your dashboard to enable targeted reporting and data views.
      </Text>
      <Box
        display="flex"
        alignItems={{ base: 'flex-start', m: 'center' }}
        justifyContent="space-between"
        flexDirection={{ base: 'column', m: 'row' }}
        gap="spacing.2"
      >
        <Text weight="semibold">Group Name</Text>
        <Button
          icon={PlusIcon}
          variant="tertiary"
          size="small"
          onClick={() => updateModalStatus(StoreGroupModalStatus.CREATE)}
          data-analytics-name="add-new-store-group"
        >
          Add New Group
        </Button>
      </Box>
      {modalStatus === StoreGroupModalStatus.CREATE && (
        <StoreGroupModal showModal title="Add New Group" submitBtnText="Add" />
      )}
      {isDeletedStoresListFetching ? (
        renderLoader('Store Groups list loading')
      ) : (
        <Box
          display="flex"
          flexDirection="column"
          gap="spacing.4"
          height="350px"
          overflow="auto"
          padding="spacing.2"
        >
          {storeGroupsList.map((storeGroup: StoreGroupForListing) => (
            <StoreGroupCard
              activeStoreGroup={selectedStoreGroupInfo?.id === storeGroup.id}
              key={storeGroup?.id}
              storeGroupInfo={storeGroup}
              onCardSelect={() => {
                setSelectedStoreGroupInfo(storeGroup);
                if (storesFilterPayload.offset) {
                  setStoresFilterOffset(0);
                }
              }}
            />
          ))}
          {isFetching && renderLoader('Store Groups list loading')}
          <Box ref={storeGroupsListRef} />
        </Box>
      )}
    </>
  );
};

export default StoreGroupsContainer;
