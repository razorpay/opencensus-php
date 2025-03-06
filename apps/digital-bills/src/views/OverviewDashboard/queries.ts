import { gql } from 'graphql-tag';

export const BILL_TRANSACTION_STATS_QUERY = gql`
  query BillTransactionStats(
    $fromDate: DateTime!
    $toDate: DateTime!
    $dataLevel: QueryDataLevelEnum
  ) {
    billTransactionStats(fromDate: $fromDate, toDate: $toDate, dataLevel: $dataLevel) {
      totalTransactions
      treesSaved
      transactionSummary {
        DIGITAL
        DIGITAL_PRINT
        PRINT
        DISCARDED
      }
      transactionOverview {
        date
        totalTransactions
        transactions {
          DIGITAL
          DIGITAL_PRINT
          PRINT
          DISCARDED
        }
      }
    }
  }
`;

export const BILL_SALES_STATS_QUERY = gql`
  query BillSalesStats($fromDate: DateTime!, $toDate: DateTime!, $dataLevel: QueryDataLevelEnum) {
    billSalesStats(fromDate: $fromDate, toDate: $toDate, dataLevel: $dataLevel) {
      avgSales
      totalSales
      overall {
        avgSales
        date
        totalSales
      }
      fnb {
        date
        totalSales
      }
      movie {
        date
        totalSales
      }
    }
  }
`;

export const BILL_WALLET_BALANCE_QUERY = gql`
  query BillWalletBalance {
    billWalletBalance {
      balance
    }
  }
`;
