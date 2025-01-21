import type { TransactionType } from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/types';

type TransationStat = Record<TransactionType, number>;

type TransactionOverviewStat = {
  date: string;
  totalTransactions: number;
  transactions: TransationStat;
};

type BillTransactionStats = {
  totalTransactions: number;
  treesSaved: number;
  transactionSummary: TransationStat;
  transactionOverview: TransactionOverviewStat[];
};

type BillTransactionStatsResponse = {
  billTransactionStats: BillTransactionStats;
};

type BillWalletBalanceResponse = {
  billWalletBalance: {
    balance: number;
  };
};

type SalesStat = {
  date: string;
  totalSales: number;
  avgSales: number;
};
type BillBySalesStats = {
  totalSales: number;
  avgSales: number;
  overall: SalesStat[];
  fnb: SalesStat[];
  movie: SalesStat[];
};

type BillSalesStatsResponse = {
  billSalesStats: BillBySalesStats;
};

export { BillTransactionStatsResponse, BillSalesStatsResponse, BillWalletBalanceResponse };
