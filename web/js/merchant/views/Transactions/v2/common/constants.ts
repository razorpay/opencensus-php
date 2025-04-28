import { Theme } from '@razorpay/blade/components';

import { getCurrentYear } from 'common/utils/date-utils';

export const headerActionTarget =
  '.tabbed-container > #transactions-header, tabbed-container > #transactions-header';

export const mobileBreakoints: Readonly<Array<keyof Theme['breakpoints']>> = [
  'base',
  'xs',
  's',
  'm',
];

export enum TransactionsEntityRoute {
  PAYMENTS = '/payments',
  BATCH_PAYMENTS = '/payments/batchuploads',
  DISPUTES = '/disputes',
  FAILED_PAYMENTS = '/failed-payments',
  ORDERS = '/orders',
  REFUNDS = '/refunds',
  BATCH_REFUNDS = '/refunds/batchuploads',
  BATCH_REFUNDS_UPLOAD = '/refunds/batchupload',
  SUCCESS_RATE = '/success-rate',
  UPLOAD_INVOICES = '/payments/b2b-exports',
  INVOICES = '/payments/invoices',
}

export const TransactionsPagesMap = {
  [TransactionsEntityRoute.PAYMENTS]: 'Payments',
  [TransactionsEntityRoute.BATCH_PAYMENTS]: 'Batch Payments',
  [TransactionsEntityRoute.UPLOAD_INVOICES]: 'Upload Invoices',
  [TransactionsEntityRoute.INVOICES]: 'Invoices',
  [TransactionsEntityRoute.DISPUTES]: 'Disputes',
  [TransactionsEntityRoute.FAILED_PAYMENTS]: 'Failed Payments',
  [TransactionsEntityRoute.ORDERS]: 'Orders',
  [TransactionsEntityRoute.REFUNDS]: 'Refunds',
  [TransactionsEntityRoute.BATCH_REFUNDS]: 'Batch Refunds',
  [TransactionsEntityRoute.BATCH_REFUNDS_UPLOAD]: 'Batch Refunds Upload',
  [TransactionsEntityRoute.SUCCESS_RATE]: 'Success Rate',
};

export const ALL_LABEL = 'All';
export const ALL_VALUE = 'all';

export const TODAY = 'today';
export const LAST_7_DAYS = 'last7Days';
export const LAST_30_DAYS = 'last30Days';
export const LAST_90_DAYS = 'last90Days';
export const CURRENT_YEAR_JAN_TILL_DATE = 'currentYearJanTillDate';
export const THIS_FINANCIAL_YEAR = 'thisFinancialYear';
export const CUSTOM = 'custom';
export const durationOptionsMap = {
  [TODAY]: 'Today',
  [LAST_7_DAYS]: 'Last 7 days',
  [LAST_30_DAYS]: 'Last 30 days',
  [LAST_90_DAYS]: 'Last 90 days',
  [CURRENT_YEAR_JAN_TILL_DATE]: `Jan ${getCurrentYear()} - till date`,
  [THIS_FINANCIAL_YEAR]: 'This financial year',
  [CUSTOM]: 'Custom',
};

export enum SearchQueryParam {
  ID = 'id',
  EMAIL = 'email',
  CONTACT = 'contact',
  COUNTRY_CODE = 'country_code',
  ORDER_ID = 'order_id',
  PAYMENT_ID = 'payment_id',
  STATUS = 'status',
  FROM = 'from',
  TO = 'to',
  METHOD = 'method',
  PUBLIC_STATUS = 'public_status',
  NOTES = 'notes',
  POS_ORDER_ID = 'pos_order_id',
}

export const MOBILE_CALENDAR_NUMBER_OF_MONTHS = 1;
export const DESKTOP_CALENDAR_NUMBER_OF_MONTHS = 2;
