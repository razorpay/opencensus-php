import React, { useEffect } from 'react';
import { Box, Heading, Button, EditIcon, TrashIcon } from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';

import { graphqlRequest } from 'common/services/graphql/graphql-client';
import { StoreGroupModalStatus } from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/constants';
import DeleteModal from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/components/DeleteModal';
import StoreGroupModal from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/components/StoreGroupModal';
import { useStoreGroupsStore } from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/stores/storeGroupsStore';
import StoresSearchComponent from 'merchant/views/StoreSettings/StoresList/containers/StoresTableContainer/components/StoresSearchComponent';
import StoresTableComponent from 'merchant/views/StoreSettings/StoresList/containers/StoresTableContainer/components/StoresTableComponent';
import { STORES_DATA_QUERY } from 'merchant/views/StoreSettings/StoresList/containers/StoresTableContainer/queries';
import { useStoresTablePayloadStore } from 'merchant/views/StoreSettings/StoresList/containers/StoresTableContainer/stores/storesTablePayloadStore';
import { transformFilterPayload } from 'merchant/views/StoreSettings/StoresList/containers/StoresTableContainer/utils';

import type { StoreGroupForListing } from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/types';
import type { StoresDataResponse } from 'merchant/views/StoreSettings/StoresList/containers/StoresTableContainer/types';

const StoresTableContainer = (): React.ReactElement => {
  const { modalStatus, updateModalStatus, selectedStoreGroupInfo, updateDefaultAllStoresGroup } =
    useStoreGroupsStore();
  const {
    storesFilterPayload,
    setStoresFilterSearchTerm,
    setStoresFilterStoreType,
    setStoresFilterLinkedProducts,
    setStoresFilterOffset,
    setStoresFilterLimit,
    setStoreSearchByColumn,
  } = useStoresTablePayloadStore();

  const {
    data: storesResponse,
    isFetching,
    refetch,
  } = useQuery<StoresDataResponse>({
    enabled: true,
    refetchOnWindowFocus: false,
    queryKey: [
      'stores_table_data',
      {
        offset: storesFilterPayload.offset,
        limit: storesFilterPayload.limit,
        selectedStoreGroupId: selectedStoreGroupInfo?.id,
        storeType: storesFilterPayload.storeType,
        linkedProducts: storesFilterPayload.linkedProducts,
      },
    ],
    queryFn: () => {
      const variables = transformFilterPayload(
        storesFilterPayload,
        selectedStoreGroupInfo as StoreGroupForListing,
      );
      // check if the search term has been trimmed and update the store
      if (variables.searchTerm !== storesFilterPayload.searchTerm) {
        setStoresFilterSearchTerm(variables.searchTerm);
      }
      return graphqlRequest({
        document: STORES_DATA_QUERY,
        variables,
      });
    },
  });

  const totalItemCount = storesResponse?.stores?.total ?? 0;
  const storesData = storesResponse?.stores?.stores ?? [];

  useEffect(() => {
    if (
      selectedStoreGroupInfo?.id === 'allStores' &&
      !storesFilterPayload.searchTerm &&
      !storesFilterPayload.linkedProducts &&
      storesFilterPayload.storeType === 'ALL'
    ) {
      updateDefaultAllStoresGroup({ ...selectedStoreGroupInfo, storesCount: totalItemCount });
    }
  }, [selectedStoreGroupInfo?.id, totalItemCount]);

  const applyFilters = () => {
    // if offset is not 0, reset the offset to fetch the search results from the first page
    !storesFilterPayload.offset ? refetch() : setStoresFilterOffset(0);
  };

  return (
    <>
      <Box
        display="flex"
        justifyContent="space-between"
        flexDirection={{ base: 'column', m: 'row' }}
        gap="spacing.3"
      >
        <Heading size="large" color="surface.text.gray.subtle">
          {selectedStoreGroupInfo?.name || '-'}
        </Heading>
        {selectedStoreGroupInfo?.id !== 'allStores' &&
          selectedStoreGroupInfo?.id !== 'deletedStores' && (
            <Box display="flex" gap="spacing.5">
              <Button
                variant="tertiary"
                size="small"
                icon={EditIcon}
                onClick={() => updateModalStatus('update')}
              >
                Edit Group
              </Button>
              <Button
                variant="tertiary"
                size="small"
                icon={TrashIcon}
                onClick={() => updateModalStatus('delete')}
              >
                Delete Group
              </Button>
            </Box>
          )}
      </Box>
      <DeleteModal />
      <StoreGroupModal
        showModal={modalStatus === StoreGroupModalStatus.UPDATE}
        title="Edit Group"
        submitBtnText="Save"
      />
      <StoresSearchComponent
        storeTypeProps={{
          selectedStoreType: storesFilterPayload.storeType,
          setStoresFilterStoreType,
        }}
        linkedProductsProps={{
          selectedLinkedProducts: storesFilterPayload.linkedProducts,
          setStoresFilterLinkedProducts,
        }}
        searchColumnProps={{
          selectedSearchByColumn: storesFilterPayload.searchColumn,
          setStoreSearchByColumn,
        }}
        searchProps={{
          searchTerm: storesFilterPayload.searchTerm,
          setStoresFilterSearchTerm,
        }}
        paginationProps={{
          offset: storesFilterPayload.offset,
          setStoresFilterOffset,
        }}
        fetchFilteredStoresData={applyFilters}
      />
      <StoresTableComponent
        tableProps={{
          storesData,
          totalItemCount,
          isRefreshing: isFetching,
          defaultPageSize: storesFilterPayload.limit,
          changePage: setStoresFilterOffset,
          changePageSize: setStoresFilterLimit,
          currentPage: storesFilterPayload.offset / storesFilterPayload.limit,
        }}
        showCreateStoreButton={selectedStoreGroupInfo?.id === 'allStores'}
      />
    </>
  );
};

export default StoresTableContainer;
