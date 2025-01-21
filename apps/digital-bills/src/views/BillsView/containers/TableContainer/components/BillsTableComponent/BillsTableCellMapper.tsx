import React from 'react';
import { Box } from '@razorpay/blade/components';

import DateTimeCell from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/components/BillsTableComponent/Cells/DateTimeCell';
import StoreDetailsCell from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/components/BillsTableComponent/Cells/StoreDetailsCell';
import TransactionTypeCell from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/components/BillsTableComponent/Cells/TransactionTypeCell';
import StatusCell from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/components/BillsTableComponent/Cells/StatusCell';
import InvoiceAmountCell from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/components/BillsTableComponent/Cells/InvoiceAmountCell';
import ContactCell from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/components/BillsTableComponent/Cells/ContactCell';
import InvoiceIdCell from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/components/BillsTableComponent/Cells/InvoiceIdCell';
import EmailCell from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/components/BillsTableComponent/Cells/EmailCell';
import {
  BILL_AMOUNT_COL_KEY,
  BILL_CONTACT_COL_KEY,
  BILL_DATE_COL_KEY,
  BILL_EMAIL_COL_KEY,
  BILL_ID_COL_KEY,
  BILL_STATUS_COL_KEY,
  BILL_STORE_COL_KEY,
  BILL_TRANSACTION_COL_KEY,
} from '@apps/digital-bills/src/utils/constants';

import type { Bill } from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/types';

const CellComponentMapper = ({
  tableItem,
  itemKey,
}: {
  tableItem: Bill;
  itemKey: string;
}): React.ReactElement => {
  const cellComponentMap: { [key: string]: React.ReactElement } = {
    [BILL_ID_COL_KEY]: (
      <InvoiceIdCell billId={tableItem.id} legacyEntityId={tableItem.legacyEntityId} />
    ),
    [BILL_CONTACT_COL_KEY]: (
      <ContactCell
        contact={tableItem.user.phone.number}
        countryCode={tableItem.user.phone.countryCode}
      />
    ),
    [BILL_DATE_COL_KEY]: <DateTimeCell billCreationTime={tableItem.dates.createdAt} />,
    [BILL_STORE_COL_KEY]: <StoreDetailsCell store={tableItem.store} brand={tableItem.brand} />,
    [BILL_AMOUNT_COL_KEY]: <InvoiceAmountCell amount={tableItem.invoice.amount.value} />,
    [BILL_TRANSACTION_COL_KEY]: <TransactionTypeCell transactionType={tableItem.transactionType} />,
    [BILL_STATUS_COL_KEY]: <StatusCell deliveryStatus={tableItem.deliveryStatus} />,
    [BILL_EMAIL_COL_KEY]: (
      <Box maxWidth="100px" whiteSpace="normal">
        <EmailCell email={tableItem.user.email} />
      </Box>
    ),
  };
  return cellComponentMap[itemKey];
};

export default CellComponentMapper;
