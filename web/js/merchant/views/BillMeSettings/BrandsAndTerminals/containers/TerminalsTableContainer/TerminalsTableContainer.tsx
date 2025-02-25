import React from 'react';
import { Box, useToast } from '@razorpay/blade/components';
import { useQuery, useMutation } from '@tanstack/react-query';

import { graphqlRequest, graphqlRequestMutation } from '@federated/apps/shell/graphql';

import TerminalsSearchComponent from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/TerminalsTableContainer/components/TerminalsSearchComponent';
import TerminalsTableComponent from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/TerminalsTableContainer/components/TerminalsTableComponent';
import { UPDATE_TERMINAL_MUTATION } from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/TerminalsTableContainer/mutations';
import { TERMINALS_TABLE_DATA_QUERY } from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/TerminalsTableContainer/queries';
import { useTerminalsTablePayloadStore } from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/TerminalsTableContainer/stores/terminalsTablePayloadStore';
import { transformFilterPayload } from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/TerminalsTableContainer/utils';

import type { TerminalsResponse } from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/TerminalsTableContainer/types';

const TerminalsTableContainer = (): React.ReactElement => {
  const {
    terminalsFilterPayload,
    setTerminalsFilterLimit,
    setTerminalsFilterOffset,
    setTerminalsFilterSearch,
    setTerminalsFilterStatus,
    setTerminalsSearchByColumn,
  } = useTerminalsTablePayloadStore();

  const toast = useToast();

  const {
    data: terminalsResponse,
    isFetching: isTerminalsListLoading,
    refetch,
  } = useQuery<TerminalsResponse>({
    enabled: true,
    refetchOnWindowFocus: false,
    queryKey: [
      'terminals_table_data',
      {
        limit: terminalsFilterPayload.limit,
        offset: terminalsFilterPayload.offset,
        isActive: terminalsFilterPayload.isActive,
      },
    ],
    queryFn: () => {
      const variables = transformFilterPayload(terminalsFilterPayload);
      // check if the search term has been trimmed and update the store
      if (terminalsFilterPayload.searchTerm !== variables.searchTerm) {
        setTerminalsFilterSearch(variables.searchTerm);
      }
      return graphqlRequest({
        document: TERMINALS_TABLE_DATA_QUERY,
        variables,
      });
    },
  });

  const { mutate: terminalUpdateMutation, isLoading: isTerminalUpdateLoading } = useMutation({
    mutationFn: ({ id, isActive }: { id: string; isActive: boolean }) =>
      graphqlRequestMutation({
        document: UPDATE_TERMINAL_MUTATION,
        variables: { id, isActive },
      }),
    onSettled: (response) => {
      if (!response.storeTerminalUpdate.success) {
        toast.show({
          type: 'informational',
          color: 'negative',
          content: response.storeTerminalUpdate.message,
        });
      } else {
        toast.show({
          type: 'informational',
          color: 'positive',
          content: 'Terminal Status updated successfully!',
        });
        refetch();
      }
    },
  });

  const totalItemCount = terminalsResponse?.storeTerminals?.total ?? 0;
  const terminalsData = terminalsResponse?.storeTerminals?.storeTerminals ?? [];

  const applyFilters = () => {
    // if offset is not 0, reset the offset to fetch the search results from the first page
    !terminalsFilterPayload.offset ? refetch() : setTerminalsFilterOffset(0);
  };

  return (
    <Box>
      <TerminalsSearchComponent
        statusProps={{
          selectedTerminalsStatus: terminalsFilterPayload.isActive,
          setTerminalsFilterStatus,
        }}
        searchColumnProps={{
          selectedSearchByColumn: terminalsFilterPayload.searchColumn,
          setTerminalsSearchByColumn,
        }}
        searchProps={{
          searchTerm: terminalsFilterPayload.searchTerm,
          setTerminalsFilterSearch,
        }}
        paginationProps={{
          offset: terminalsFilterPayload.offset,
          setTerminalsFilterOffset,
        }}
        fetchFilteredTerminalsData={applyFilters}
      />
      <TerminalsTableComponent
        tableProps={{
          terminalsData,
          totalItemCount,
          isRefreshing: isTerminalsListLoading || isTerminalUpdateLoading,
          defaultPageSize: terminalsFilterPayload.limit,
          changePage: setTerminalsFilterOffset,
          changePageSize: setTerminalsFilterLimit,
          currentPage: terminalsFilterPayload.offset / terminalsFilterPayload.limit,
        }}
        onToggleStatus={({ id, isActive }) => terminalUpdateMutation({ id, isActive })}
      />
    </Box>
  );
};

export default TerminalsTableContainer;
