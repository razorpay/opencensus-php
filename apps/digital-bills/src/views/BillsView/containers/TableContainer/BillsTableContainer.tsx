import React, { useState, useEffect } from 'react';
import { Box, Spinner } from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';

import { graphqlRequest } from '@apps/digital-bills/src/utils/graphql';
import RetryOnError from '@apps/digital-bills/src/common/components/RetryOnError';
import StoreFilterModal from '@apps/digital-bills/src/common/components/StoreFilterModal';
import { useBillsTableConfigStore } from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/stores/billsTableConfigStore';
import { useBillsTablePayloadStore } from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/stores/billsTablePayloadStore';
import { BillsTableComponent } from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/components/BillsTableComponent';
import { BillsSearchComponent } from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/components/BillsSearchComponent';
import { BILLS_TABLE_DATA_QUERY } from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/queries';

import type {
  BillsDataResponse,
  Bill,
  StoreGroupsDataResponse,
  StoresDataResponse,
} from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/types';

type BillsTableContainerProps = {
  storeGroupsResponse: StoreGroupsDataResponse | undefined;
  storesResponse: StoresDataResponse | undefined;
  isStoresInfoLoading?: boolean;
  hasErrorInStoresInfo?: boolean;
  retryFn?: () => void;
};

const BillsTableContainer = ({
  storeGroupsResponse,
  storesResponse,
  isStoresInfoLoading = false,
  hasErrorInStoresInfo = false,
  retryFn,
}: BillsTableContainerProps): React.ReactElement => {
  const [isEditColumnOpen, setIsEditColumnOpen] = useState<boolean>(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState<boolean>(false);
  const [isTableSelectable, setIsTableSelectable] = useState<boolean>(false);
  const [selectedBills, setSelectedBills] = useState<Bill[]>([]);

  const tableColumns = useBillsTableConfigStore((state) => state.tableColumns);

  const {
    billsFilterPayload,
    setBillsFilterOffset,
    resetBillsFilter,
    isStoreFilterModalOpen,
    setStoreFilterModalOpen,
    setBillsStoreIds,
    setStoreGroupInputValue,
    selectedModalStores,
    setSelectedModalStores,
    filtersResetAt,
  } = useBillsTablePayloadStore();
  const {
    data: billsResponse,
    isError: isErrorInBillsInfo,
    isFetching,
    refetch,
  } = useQuery<BillsDataResponse>({
    refetchOnWindowFocus: false,
    queryKey: ['bills_table_data', { offset: billsFilterPayload.offset, filtersResetAt }],
    queryFn: () =>
      graphqlRequest({
        document: BILLS_TABLE_DATA_QUERY,
        variables: billsFilterPayload,
      }),
  });
  const totalItemCount = billsResponse?.bills?.total ?? 0;
  const billsData = billsResponse?.bills?.bills ?? [];

  useEffect(() => {
    return () => {
      resetBillsFilter();
    };
  }, []);

  const applyFilters = () => {
    if (!billsFilterPayload.offset) {
      refetch();
    } else {
      setBillsFilterOffset(0);
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
      {/* Store Filter Modal */}
      <StoreFilterModal
        key={isStoreFilterModalOpen ? 'modal-opened' : 'modal-closed'}
        selectedStores={selectedModalStores}
        onStoresSelect={(selectedStores, storesType) => {
          setBillsStoreIds(selectedStores);
          setStoreFilterModalOpen(false);
          setSelectedModalStores(storesType);
          setStoreGroupInputValue(`Stores Selected (${selectedStores.length})`);
        }}
        isOpen={isStoreFilterModalOpen}
        dismiss={() => {
          setStoreFilterModalOpen(false);
        }}
      />
      <BillsSearchComponent
        storesResponse={storesResponse}
        storeGroupsResponse={storeGroupsResponse}
        resetBillsFilter={resetBillsFilter}
        fetchFilteredBillsData={applyFilters}
      />
      {isErrorInBillsInfo ? (
        <Box marginTop="spacing.9">
          <RetryOnError errorText="Error in fetching Bills information" retryFn={refetch} />
        </Box>
      ) : (
        <BillsTableComponent
          editColumnModalProps={{
            isEditColumnOpen,
            dismissEditColumn: () => setIsEditColumnOpen(false),
            openEditColumn: () => setIsEditColumnOpen(true),
          }}
          deleteModalProps={{
            isDeleteModalOpen,
            dismissDeleteModal: () => setIsDeleteModalOpen(false),
            openDeleteModal: () => setIsDeleteModalOpen(true),
          }}
          tableProps={{
            isRefreshing: isFetching,
            billsData,
            totalItemCount,
            isTableSelectable,
            selectedBills,
            defaultPageSize: billsFilterPayload.limit,
            tableColumns,
            setSelectedBills,
            changePage: setBillsFilterOffset,
            setIsTableSelectable,
            currentPage: billsFilterPayload.offset / billsFilterPayload.limit,
          }}
        />
      )}
    </Box>
  );
};

export default BillsTableContainer;
