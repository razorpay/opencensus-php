export const DIGITAL = 'DIGITAL';
export const DIGITAL_PRINT = 'DIGITAL_PRINT';
export const PRINT = 'PRINT';
export const DISCARDED = 'DISCARDED';

export const BillTransactionTypes = {
  [DIGITAL]: {
    label: 'Digital',
  },
  [DIGITAL_PRINT]: {
    label: 'Digital Print',
  },
  [PRINT]: {
    label: 'Print',
  },
  [DISCARDED]: {
    label: 'Discarded',
  },
} as const;

export const SalesAggregationTypes = Object.freeze({
  TotalSales: 'Total Bill Value',
  AverageSales: 'Avg. Bill Value',
});
// sales aggregation types
export const TOTAL_SALES = 'Total Bill Value';
export const AVERAGE_SALES = 'Avg. Bill Value';
export const TOTAL_TRANSACTIONS = 'Total Bills Generated';

export enum DurationRange {
  // Since, old BillMe service is handling a max of 90 days range,
  // we are setting the range from start date (start of the day) + 1 to end date (end of the day)
  Last7Days = '6',
  Last30Days = '29',
  Last90Days = '89',
  Custom = 'custom',
}

export const DurationRangeLabels = {
  [DurationRange.Last7Days]: 'Last 7 Days',
  [DurationRange.Last30Days]: 'Last 30 Days',
  [DurationRange.Last90Days]: 'Last 90 Days',
  [DurationRange.Custom]: 'Custom',
} as const;

export const TransactionTypes = {
  DIGITAL: {
    Name: 'Digital',
    Value: 'DIGITAL',
  },
  PRINT: {
    Name: 'Print',
    Value: 'PRINT',
  },
  DIGITAL_PRINT: {
    Name: 'Digital + Print',
    Value: 'DIGITAL_PRINT',
  },
} as const;

export const BillStatusTypes = {
  ATTEMPTED: {
    Name: 'Attempted',
    Value: 'ATTEMPTED',
  },
  FAILED: {
    Name: 'Failed',
    Value: 'FAILED',
  },
  DELIVERED: {
    Name: 'Delivered',
    Value: 'DELIVERED',
  },
  PENDING: {
    Name: 'Pending',
    Value: 'PENDING',
  },
} as const;

export const StoreStatusTypes = {
  ACTIVE: {
    Name: 'Active',
    Value: 'ACTIVE',
  },
  INACTIVE: {
    Name: 'Inactive',
    Value: 'INACTIVE',
  },
} as const;

export const BillSearchByOptions = {
  InvoiceNumber: {
    Name: 'Invoice Number',
    Value: 'invoiceNumber',
  },
  StoreCode: {
    Name: 'Store Code',
    Value: 'storeCode',
  },
  Email: {
    Name: 'Email',
    Value: 'email',
  },
  PhoneNumber: {
    Name: 'Phone Number',
    Value: 'contact',
  },
} as const;

// Bills Data Table Column Keys
export const BILL_ID_COL_KEY = 'billId';
export const BILL_CONTACT_COL_KEY = 'billContact';
export const BILL_DATE_COL_KEY = 'billDate';
export const BILL_STORE_COL_KEY = 'billStore';
export const BILL_AMOUNT_COL_KEY = 'billAmount';
export const BILL_TRANSACTION_COL_KEY = 'billTransaction';
export const BILL_STATUS_COL_KEY = 'billStatus';
export const BILL_EMAIL_COL_KEY = 'billEmail';

// Bills Data Table Column Names
export const BILL_ID_COL_NAME = 'Bill ID';
export const BILL_CONTACT_COL_NAME = 'Phone Number';
export const BILL_DATE_COL_NAME = 'Date & Time';
export const BILL_STORE_COL_NAME = 'Store Details';
export const BILL_AMOUNT_COL_NAME = 'Amount';
export const BILL_TRANSACTION_COL_NAME = 'Transaction';
export const BILL_STATUS_COL_NAME = 'Status';
export const BILL_EMAIL_COL_NAME = 'Email';

export const DEFAULT_COUNTRY_CODE = '+91';

export const StoreSearchByOptions = {
  storeCode: {
    name: 'Store Code',
    value: 'storeCode',
  },
  storeName: {
    name: 'Store Name',
    value: 'storeName',
  },
} as const;

export const LOCALE_IN = 'en-IN';
export const DIGITAL_BILLS = 'Digital Bills';
export const zIndicesMap = {
  modal: 1000,
  modalOverlay: 1001,
  dropdownOverlay: 1002,
};

export const ERROR_PAGE_DESCRIPTION = 'We are facing some issues. Please try again later.';
export const MAX_DATE_FOR_DATE_PICKER = new Date();
export const DATA_LEVEL_FOR_AGGREGATIONS_INFO = 'detailed';
