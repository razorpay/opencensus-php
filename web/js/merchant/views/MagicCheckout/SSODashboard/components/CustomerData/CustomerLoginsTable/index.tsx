import React from 'react';
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
} from '@razorpay/blade/components';
import type { SSOLoginDetails } from 'merchant/views/MagicCheckout/SSODashboard/types';
import { TABLE_CELLS } from 'merchant/views/MagicCheckout/SSODashboard/components/CustomerData/CustomerLoginsTable/constants';

interface CustomerLoginTableProps {
  isFetching: boolean;
  data: SSOLoginDetails[];
}

const CustomerLoginTable = ({ data, isFetching }: CustomerLoginTableProps) => {
  const formattedData = {
    nodes: data.map((item, index) => ({
      id: index,
      ...item,
    })),
  };
  return (
    <Box backgroundColor="surface.background.gray.intense" overflow="auto" paddingTop="spacing.6">
      <Table data={formattedData} showBorderedCells isRefreshing={isFetching}>
        {(tableData) => (
          <>
            <TableHeader>
              <TableHeaderRow>
                {TABLE_CELLS.map((cell) => (
                  <TableHeaderCell>
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
              {tableData.map((tableItem, index) => (
                <TableRow key={index} item={tableItem}>
                  {TABLE_CELLS.map(({ Component: CellComponent, key }, cellIndex) => (
                    <TableCell key={`${index}${cellIndex}`}>
                      <Box display="flex" flexDirection="row" alignItems="center">
                        <Text size="small">
                          <CellComponent value={tableItem?.[key]} />
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

export default CustomerLoginTable;
