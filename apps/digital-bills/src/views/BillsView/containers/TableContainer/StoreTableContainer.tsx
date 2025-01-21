import React, { useEffect } from 'react';
import { Box, Spinner } from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';

import type {
  StoreAggregationDataResponse,
  StoreGroupsDataResponse,
  StoresDataResponse,
} from './types';
import { graphqlRequest } from '@apps/digital-bills/src/utils/graphql';
import RetryOnError from '@apps/digital-bills/src/common/components/RetryOnError';
import StoreFilterModal from '@apps/digital-bills/src/common/components/StoreFilterModal';
import { StoreSearchComponent } from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/components/StoreSearchComponent';
import { StoreTableComponent } from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/components/StoreTableComponent';
import { useStoreTablePayloadStore } from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/stores/storeTablePayloadStore';
import { useBillsTablePayloadStore } from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/stores/billsTablePayloadStore';
import { STORES_AGGREGATION_DATA_QUERY } from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/queries';
import { transformFilterPayload } from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/utils';

type StoreTableContainerProps = {
  storeGroupsResponse: StoreGroupsDataResponse | undefined;
  storesResponse: StoresDataResponse | undefined;
  isStoresInfoLoading?: boolean;
  hasErrorInStoresInfo?: boolean;
  retryFn?: () => void;
};

const StoreTableContainer = ({
  storeGroupsResponse,
  storesResponse,
  isStoresInfoLoading = false,
  hasErrorInStoresInfo = false,
  retryFn,
}: StoreTableContainerProps): React.ReactElement => {
  const {
    storeFilterPayload,
    setStoreFilterOffset,
    setStoreFilterMaxAmount,
    setStoreFilterMinAmount,
    setStoreFilterSearch,
    setStoreFilterStatus,
    setStoreFilterDateRange,
    resetStoreFilter,
    setSelectedModalStores,
    selectedModalStores,
    setSelectedStoreIds,
    setStoreGroupInputValue,
    filtersResetAt,
  } = useStoreTablePayloadStore();

  const {
    data: storeAggregationResponse,
    isError: isErrorInStorewiseAggregation,
    isFetching,
    refetch,
  } = useQuery<StoreAggregationDataResponse>({
    queryKey: ['store_table_data', storeFilterPayload.offset, filtersResetAt],
    queryFn: () =>
      graphqlRequest({
        document: STORES_AGGREGATION_DATA_QUERY,
        variables: transformFilterPayload(storeFilterPayload),
      }),
    refetchOnWindowFocus: false,
  });

  const storeAggregationData = storeAggregationResponse?.billStoresAggregation?.stores ?? [];
  const totalItemCount = storeAggregationResponse?.billStoresAggregation?.total ?? 0;
  const { setStoreFilterModalOpen, isStoreFilterModalOpen } = useBillsTablePayloadStore();

  useEffect(() => {
    return () => {
      resetStoreFilter();
    };
  }, []);

  const applyFilters = () => {
    if (!storeFilterPayload.offset) {
      refetch();
    } else {
      setStoreFilterOffset(0);
    }
  };

  if (hasErrorInStoresInfo) {
    return <RetryOnError errorText="Error in fetching stores information" retryFn={retryFn} />;
  }
  if (isStoresInfoLoading) {
    return (
      <Box display="flex" justifyContent="center" height="100%">
        <Spinner accessibilityLabel="Stores info loading" />
      </Box>
    );
  }

  return (
    <Box>
      <StoreFilterModal
        key={isStoreFilterModalOpen ? 'modal-opened' : 'modal-closed'}
        selectedStores={selectedModalStores}
        onStoresSelect={(selectedStores, storesType) => {
          setSelectedStoreIds(selectedStores);
          setStoreFilterModalOpen(false);
          setSelectedModalStores(storesType);
          setStoreGroupInputValue(`Stores Selected (${selectedStores.length})`);
        }}
        isOpen={isStoreFilterModalOpen}
        dismiss={() => {
          setStoreFilterModalOpen(false);
        }}
      />
      <StoreSearchComponent
        storeGroupsResponse={storeGroupsResponse}
        storesResponse={storesResponse}
        dateRangeProps={{
          selectedDateRange: [storeFilterPayload.fromDate, storeFilterPayload.toDate],
          setStoreFilterDateRange,
        }}
        statusProps={{
          selectedStoreStatus: storeFilterPayload.status,
          setStoreFilterStatus,
        }}
        amountProps={{
          minAmount: storeFilterPayload.minAmount,
          maxAmount: storeFilterPayload.maxAmount,
          setStoreFilterMinAmount,
          setStoreFilterMaxAmount,
        }}
        searchProps={{
          searchInputs: {
            storeName: storeFilterPayload.storeName,
            storeCode: storeFilterPayload.storeCode,
          },
          setStoreFilterSearch,
        }}
        resetStoreFilter={resetStoreFilter}
        fetchFilteredStoreData={applyFilters}
      />
      {isErrorInStorewiseAggregation ? (
        <Box marginTop="spacing.9">
          <RetryOnError
            errorText="Error in fetching Store wise aggregation information"
            retryFn={refetch}
          />
        </Box>
      ) : (
        <StoreTableComponent
          tableProps={{
            totalItemCount,
            isRefreshing: isFetching,
            defaultPageSize: storeFilterPayload.limit,
            storesData: storeAggregationData,
            changePage: setStoreFilterOffset,
            currentPage: storeFilterPayload.offset / storeFilterPayload.limit,
          }}
        />
      )}
    </Box>
  );
};

export default StoreTableContainer;
