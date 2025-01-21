import React from 'react';
import {
  Box,
  Table,
  TableHeader,
  TableHeaderRow,
  TableHeaderCell,
  TableBody,
  TablePagination,
  TableToolbar,
  InfoIcon,
  Text,
} from '@razorpay/blade/components';

import { TABLE_HEADERS } from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/components/StoreTableComponent/constants';
import StoreTableRow from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/components/StoreTableComponent/StoreTableRow';

import type {
  PageLimitType,
  StoreAggregation,
} from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/types';

type StoreTableComponentProps = {
  tableProps: {
    isRefreshing: boolean;
    defaultPageSize: PageLimitType;
    storesData: StoreAggregation[];
    changePage: (offset: number) => void;
    totalItemCount: number;
    currentPage: number;
  };
};

const StoreTableComponent = ({
  tableProps: {
    isRefreshing,
    defaultPageSize,
    storesData,
    changePage,
    totalItemCount,
    currentPage,
  },
}: StoreTableComponentProps): React.ReactElement => {
  return (
    <Box
      backgroundColor="surface.background.gray.intense"
      minHeight="400px"
      overflow="auto"
      padding="spacing.5"
    >
      <Table
        isRefreshing={isRefreshing}
        data={{
          nodes: storesData,
        }}
        gridTemplateColumns="20% 10% 15% 15% 10% 10% 10% 10%"
        pagination={
          storesData.length > 0 ? (
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
        toolbar={<TableToolbar />}
      >
        {(tableData) => (
          <>
            <TableHeader>
              <TableHeaderRow>
                {TABLE_HEADERS.map((header) => (
                  <TableHeaderCell key={header}>
                    <Box whiteSpace="normal">{header}</Box>
                  </TableHeaderCell>
                ))}
              </TableHeaderRow>
            </TableHeader>
            {storesData?.length ? (
              <TableBody>
                {tableData.map((tableItem) => (
                  <StoreTableRow key={tableItem?.id} tableItem={tableItem} />
                ))}
              </TableBody>
            ) : (
              // Grid column end value is given in accordance to number of table columns + 1
              <Box gridColumn="1/9" padding="spacing.8">
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

export default StoreTableComponent;
