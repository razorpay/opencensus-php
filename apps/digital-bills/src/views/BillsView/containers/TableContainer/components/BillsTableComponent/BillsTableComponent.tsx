import React from 'react';
import {
  Box,
  Table,
  TableToolbar,
  Text,
  TableToolbarActions,
  Button,
  TablePagination,
  TableHeader,
  TableHeaderRow,
  TableHeaderCell,
  TableBody,
  TableRow,
  CheckSquareIcon,
  TrashIcon,
  TableCell,
  useToast,
  InfoIcon,
} from '@razorpay/blade/components';
import { useMutation } from '@tanstack/react-query';

import { graphqlRequestMutation } from '@apps/digital-bills/src/utils/graphql';
import { queryClient } from '@apps/digital-bills/src/bootstrap/Wrapper/Wrapper';
import { BILL_BULK_DELETE } from '@apps/digital-bills/src/views/BillDetails/mutations';
import { EditColumnsModal } from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/components/EditColumnsModal';
import { TableColumns } from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/stores/billsTableConfigStore';
import { useBillsTablePayloadStore } from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/stores/billsTablePayloadStore';
import CellComponentMapper from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/components/BillsTableComponent/BillsTableCellMapper';
import DeleteModal from '@apps/digital-bills/src/views/BillDetails/containers/BillDetailContainer/components/DeleteModal';

import type { BillsTableComponentProps } from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/components/BillsTableComponent/types';

const BillsTableComponent = ({
  editColumnModalProps: { isEditColumnOpen, dismissEditColumn, openEditColumn },
  deleteModalProps: { isDeleteModalOpen, dismissDeleteModal, openDeleteModal },
  tableProps: {
    isRefreshing,
    billsData,
    totalItemCount,
    isTableSelectable,
    selectedBills,
    defaultPageSize,
    tableColumns,
    setIsTableSelectable,
    setSelectedBills,
    changePage,
    currentPage,
  },
}: BillsTableComponentProps): React.ReactElement => {
  const { show } = useToast();
  const { billsFilterPayload, filtersResetAt } = useBillsTablePayloadStore();

  const { mutate: billsBulkDeleteMutate, isLoading } = useMutation({
    mutationFn: () =>
      graphqlRequestMutation({
        document: BILL_BULK_DELETE,
        variables: { ids: selectedBills.map((bill) => bill.id) },
      }),
    onSuccess: () => {
      show({
        type: 'informational',
        content: 'Bill deleted successfully!',
        onDismissButtonClick: () => {
          dismissDeleteModal();
          queryClient.invalidateQueries({
            queryKey: ['bills_table_data', billsFilterPayload.offset, filtersResetAt],
          });
        },
      });
      dismissDeleteModal();
    },
    onError: () => {
      show({
        type: 'informational',
        color: 'negative',
        content: 'Something went wrong while deleting the bill!',
      });
    },
  });

  return (
    <Box
      backgroundColor="surface.background.gray.intense"
      minHeight="400px"
      overflow="auto"
      padding="spacing.5"
    >
      <EditColumnsModal isOpen={isEditColumnOpen} dismiss={dismissEditColumn} />
      <DeleteModal
        modalProps={{
          isOpen: isDeleteModalOpen,
          onDismiss: dismissDeleteModal,
        }}
        onDelete={billsBulkDeleteMutate}
        isLoading={isLoading}
        showPluralText
      />
      <Table
        isRefreshing={isRefreshing}
        data={{
          nodes: billsData,
        }}
        selectionType={isTableSelectable ? 'multiple' : 'none'}
        onSelectionChange={({ values }) => setSelectedBills(values)}
        pagination={
          billsData.length > 0 ? (
            <TablePagination
              showLabel
              paginationType="server"
              totalItemCount={totalItemCount}
              defaultPageSize={defaultPageSize}
              onPageChange={({ page }) => changePage(page * defaultPageSize)}
              showPageNumberSelector
              showPageSizePicker={false}
              currentPage={currentPage}
            />
          ) : (
            <></>
          )
        }
        toolbar={
          <TableToolbar>
            <TableToolbarActions>
              <Box
                display="flex"
                gap="spacing.5"
                alignContent="center"
                justifyContent={{ base: 'flex-start', l: 'flex-end' }}
                width="400px"
              >
                {isTableSelectable ? (
                  <>
                    <Box>
                      <Button variant="tertiary" onClick={() => setIsTableSelectable(false)}>
                        Cancel
                      </Button>
                    </Box>
                    <Box>
                      <Button
                        isDisabled={selectedBills.length === 0}
                        iconPosition="left"
                        icon={TrashIcon}
                        variant="primary"
                        color="negative"
                        onClick={openDeleteModal}
                      >
                        Delete
                      </Button>
                    </Box>
                  </>
                ) : (
                  <>
                    <Box>
                      <Button variant="tertiary" onClick={openEditColumn}>
                        Edit Columns
                      </Button>
                    </Box>
                    <Box display="flex" gap="spacing.5">
                      <Button
                        icon={CheckSquareIcon}
                        variant="tertiary"
                        onClick={() => setIsTableSelectable(true)}
                      />
                    </Box>
                  </>
                )}
              </Box>
            </TableToolbarActions>
          </TableToolbar>
        }
      >
        {(tableData) => (
          <>
            <TableHeader>
              <TableHeaderRow>
                {tableColumns.map((column: TableColumns) => (
                  <TableHeaderCell key={column.key} headerKey={column.key}>
                    <Box whiteSpace="normal">
                      <Text weight="medium">{column.name}</Text>
                    </Box>
                  </TableHeaderCell>
                ))}
              </TableHeaderRow>
            </TableHeader>
            {tableData.length ? (
              <TableBody>
                {tableData.map((tableItem) => (
                  <TableRow key={tableItem.id} item={tableItem}>
                    {tableColumns.map((column) => (
                      <TableCell key={column.key}>
                        <CellComponentMapper tableItem={tableItem} itemKey={column.key} />
                      </TableCell>
                    ))}
                  </TableRow>
                ))}
              </TableBody>
            ) : (
              // Grid column end value is given in accordance to number of table columns + 1
              <Box gridColumn={`1/${(tableColumns?.length || 0) + 1}`} padding="spacing.8">
                <Box gap="spacing.2" display="flex" justifyContent="center" alignItems="center">
                  <InfoIcon size="xlarge" />{' '}
                  <Text weight="semibold" variant="body">
                    No data found
                  </Text>
                </Box>
              </Box>
            )}
          </>
        )}
      </Table>
    </Box>
  );
};

export default BillsTableComponent;
