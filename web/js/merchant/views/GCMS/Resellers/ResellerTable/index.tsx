import React, { useEffect } from 'react';
import {
  Table,
  TableHeader,
  TableHeaderRow,
  TableHeaderCell,
  TableBody,
  TableCell,
  TableRow,
  TablePagination,
  Box,
  Spinner,
} from '@razorpay/blade/components';

import ResellersFilters from './ResellersFilters';
import { EmptyListWithTableRow } from 'merchant/components/EmptyList';
import useFetchResellers from './hooks/useFetchResellers';
import { RESELLER_SERVICE } from './constants';
import { trackResellerDetailsPageClicked } from 'merchant/views/GCMS/Resellers/events';
import { ResellerTableProps } from './types';
import { resellerListColumns, unmappedResellerListColumns } from './columns';

const MODES = {
  EDIT: 'edit',
  READ_ONLY: 'read',
};

function ResellerTable({
  mode,
  merchantId,
  renderLoading = null,
  accessMode = MODES.READ_ONLY,
  onSelection,
  refetchQuery = 0,
  filterOptions,
  service = RESELLER_SERVICE.ALL_RESELLERS,
  programId,
  showFilters = true,
}: ResellerTableProps) {
  const columns =
    service === RESELLER_SERVICE.ALL_RESELLERS ? resellerListColumns : unmappedResellerListColumns;
  const {
    isLoading,
    resellers,
    skip,
    setResellerName,
    setResellerStatus,
    handleSearch,
    onPageSizeChange,
    pageSize,
    // selectedResellers,
    refetch,
  } = useFetchResellers({
    mode,
    merchantId,
    programId,
    refetchQuery,
    service,
  });

  function onSelectionChange({ selectedIds }) {
    onSelection(selectedIds);
  }
  function onPageChange(values) {
    console.log(values);
  }

  let selectProps = {};

  if (accessMode === MODES.EDIT) {
    selectProps = {
      multiSelectTrigger: 'checkbox',
      selectionType: 'multiple',
      onSelectionChange,
    };
  }

  const isEmptyData = !resellers || !resellers.items || resellers.items.length === 0;

  const tableData = {
    nodes: resellers?.items.map((val) => ({ ...val, id: val.merchant_id })) ?? [],
  };

  if (isLoading && isEmptyData) {
    return (
      renderLoading || (
        <Box display="flex" alignItems="center" justifyContent="center" minHeight="200px">
          <Spinner
            accessibilityLabel="loading programs"
            label="loading..."
            labelPosition="bottom"
          />
        </Box>
      )
    );
  }

  if (!isLoading && isEmptyData) {
    return (
      <Box display="flex" alignItems="center" justifyContent="center">
        <EmptyListWithTableRow
          colSpan={8}
          description={
            <React.Fragment>
              <div>There are no resellers yet!!</div>
              <div>Start creating new resellers now.</div>
            </React.Fragment>
          }
        />
      </Box>
    );
  }

  return (
    <Box>
      {showFilters && (
        <ResellersFilters
          setStatus={setResellerStatus}
          setResellerName={setResellerName}
          onSearch={handleSearch}
          disabled={isLoading}
          filterOptions={filterOptions}
        />
      )}
      <Table
        data={tableData}
        pagination={
          <TablePagination
            defaultPageSize={pageSize}
            onPageChange={onPageChange}
            onPageSizeChange={onPageSizeChange}
            showPageSizePicker
          />
        }
        isLoading={isLoading}
        {...selectProps}
      >
        {(orderItems) => {
          return (
            <>
              <TableHeader>
                <TableHeaderRow>
                  {columns.map(({ title }) => (
                    <TableHeaderCell key={title}>{title}</TableHeaderCell>
                  ))}
                </TableHeaderRow>
              </TableHeader>
              <TableBody>
                {orderItems.map((order, index) => (
                  <TableRow key={index} item={order}>
                    {columns.map(({ title, value }) => (
                      <TableCell key={title}>{value(order)}</TableCell>
                    ))}
                  </TableRow>
                ))}
              </TableBody>
            </>
          );
        }}
      </Table>
    </Box>
  );
}

export default ResellerTable;
