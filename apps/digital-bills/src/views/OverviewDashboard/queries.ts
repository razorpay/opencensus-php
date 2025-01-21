import { gql } from 'graphql-tag';

export const BILL_TRANSACTION_STATS_QUERY = gql`
  query BillTransactionStats($fromDate: DateTime!, $toDate: DateTime!) {
    billTransactionStats(fromDate: $fromDate, toDate: $toDate) {
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
  query BillSalesStats($fromDate: DateTime!, $toDate: DateTime!) {
    billSalesStats(fromDate: $fromDate, toDate: $toDate) {
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
