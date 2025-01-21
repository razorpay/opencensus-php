import {
  TOTAL_SALES,
  AVERAGE_SALES,
  TOTAL_TRANSACTIONS,
} from '@apps/digital-bills/src/utils/constants';

export type SelectedOverviewCategory =
  | typeof TOTAL_SALES
  | typeof AVERAGE_SALES
  | typeof TOTAL_TRANSACTIONS
  | null;

export type SalesData = {
  avgSales: number | null;
  date: string;
  totalSales: number;
};

export type BillSalesStats = {
  avgSales: number;
  movie: SalesData[];
  fnb: SalesData[];
  overall: SalesData[];
  totalSales: number;
};

export type SalesResponse = {
  billSalesStats: BillSalesStats;
};

export type Transactions = {
  DIGITAL: number;
  DIGITAL_PRINT: number;
  PRINT: number;
};

export type TransactionOverview = {
  date: string;
  totalTransactions: number;
  transactions: Transactions;
};

export type BillTransactionStats = {
  totalTransactions: number;
  transactionOverview: TransactionOverview[];
  transactionSummary: Transactions;
};

export type TransactionsResponse = {
  billTransactionStats: BillTransactionStats;
};
