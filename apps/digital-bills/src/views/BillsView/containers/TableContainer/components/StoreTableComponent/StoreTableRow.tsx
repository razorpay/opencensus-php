import React from 'react';
import { Box, TableRow, TableCell } from '@razorpay/blade/components';

import StoreDetailsCell from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/components/StoreTableComponent/Cells/StoreDetailsCell';
import StatusCell from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/components/StoreTableComponent/Cells/StatusCell';
import TotalSalesCell from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/components/StoreTableComponent/Cells/TotalSalesCell';
import AverageBillingCell from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/components/StoreTableComponent/Cells/AverageBillingCell';
import { getFormattedNumber } from '@apps/digital-bills/src/utils/helpers/numberFormatting';
import { LOCALE_IN } from '@apps/digital-bills/src/utils/constants';

import type { StoreAggregation } from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/types';

type StoreTableRowProps = {
  tableItem: StoreAggregation;
};

const StoreTableRow = ({ tableItem }: StoreTableRowProps): React.ReactElement => {
  return (
    <TableRow key={tableItem?.id} item={tableItem}>
      <TableCell>
        <Box whiteSpace="normal">
          <StoreDetailsCell store={tableItem?.store} />
        </Box>
      </TableCell>
      <TableCell>
        <Box whiteSpace="normal">
          <StatusCell status={tableItem?.store?.isActive} />
        </Box>
      </TableCell>
      <TableCell>
        <Box whiteSpace="normal">
          <TotalSalesCell totalSales={tableItem?.salesInfo?.totalSales} />
        </Box>
      </TableCell>
      <TableCell>
        <Box whiteSpace="normal">
          <AverageBillingCell averageBilling={tableItem?.salesInfo?.averageSales} />
        </Box>
      </TableCell>
      <TableCell>
        <Box whiteSpace="normal">{getFormattedNumber(tableItem?.totalTransactions, LOCALE_IN)}</Box>
      </TableCell>
      <TableCell>
        <Box whiteSpace="normal">
          {getFormattedNumber(tableItem?.transactionInfo?.DIGITAL, LOCALE_IN)}
        </Box>
      </TableCell>
      <TableCell>
        <Box whiteSpace="normal">
          {getFormattedNumber(tableItem?.transactionInfo?.DIGITAL_PRINT, LOCALE_IN)}
        </Box>
      </TableCell>
      <TableCell>
        <Box whiteSpace="normal">
          {getFormattedNumber(tableItem?.transactionInfo?.PRINT, LOCALE_IN)}
        </Box>
      </TableCell>
    </TableRow>
  );
};

export default StoreTableRow;
