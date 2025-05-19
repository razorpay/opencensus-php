import React, { useMemo } from 'react';
import moment from 'moment';
import {
  Table,
  Box,
  TableCell,
  TableHeader,
  TableHeaderRow,
  TableHeaderCell,
  TableBody,
  TableRow,
  Text,
  TablePagination,
} from '@razorpay/blade/components';
import { TABLE_CELLS } from 'merchant/views/MagicCheckout/SSODashboard/components/CustomerData/CustomerTable/constants';
import type {
  SSO_CUSTOMER_FILTER,
  SSOCustomer,
} from 'merchant/views/MagicCheckout/SSODashboard/types';

interface CustomerTableProps {
  isRefreshing: boolean;
  data: SSOCustomer[];
  timeRange: {
    start: moment.Moment;
    end: moment.Moment;
  };
  currentPage: number;
  totalCount: number;
  getCustomerListData: (page?: number, type?: SSO_CUSTOMER_FILTER, search?: string) => void;
  setCurrentPage: React.Dispatch<React.SetStateAction<number>>;
}

const CustomerTable = ({
  data,
  currentPage,
  timeRange,
  isRefreshing,
  totalCount,
  getCustomerListData,
  setCurrentPage,
}: CustomerTableProps) => {
  const tableData = useMemo(
    () => ({ nodes: data.map((value) => ({ id: value.customer_id, ...value })) }),
    [data],
  );
  const onPageChange = ({ page }) => {
    if (isRefreshing) return;
    getCustomerListData(page);
    setCurrentPage(page);
  };
  return (
    <Box backgroundColor="surface.background.gray.intense" overflow="auto">
      <Table
        data={tableData}
        gridTemplateColumns={`2.5fr repeat(${TABLE_CELLS?.length - 2},1fr) 2.5fr`}
        showBorderedCells
        isRefreshing={isRefreshing}
        pagination={
          <TablePagination
            defaultPageSize={10}
            showPageNumberSelector
            showPageSizePicker={false}
            currentPage={currentPage}
            totalItemCount={totalCount}
            paginationType="server"
            onPageChange={onPageChange}
          />
        }
      >
        {(tableData) => (
          <>
            <TableHeader>
              <TableHeaderRow>
                {TABLE_CELLS.map((cell, index) => (
                  <TableHeaderCell key={index}>
                    <Box
                      display="flex"
                      flexDirection="row"
                      flex={1}
                      justifyContent="space-between"
                      alignItems="center"
                    >
                      <Text size="small" weight="semibold">
                        {cell.label}
                      </Text>
                    </Box>
                  </TableHeaderCell>
                ))}
              </TableHeaderRow>
            </TableHeader>
            <TableBody>
              {tableData.map((customer, index) => (
                <TableRow key={index} item={customer}>
                  {TABLE_CELLS.map(({ Component: CellComponent, key }, cellIndex) => (
                    <TableCell key={`${index}${cellIndex}`}>
                      <Box display="flex" flexDirection="row" alignItems="center">
                        <Text size="small">
                          <CellComponent
                            timeRange={timeRange}
                            value={customer?.[key]}
                            customer={customer}
                          />
                        </Text>
                      </Box>
                    </TableCell>
                  ))}
                </TableRow>
              ))}
            </TableBody>
          </>
        )}
      </Table>
    </Box>
  );
};

export default CustomerTable;
