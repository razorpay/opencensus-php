import { gql } from 'graphql-tag';

export const SALES_OVERVIEW_QUERY = gql`
  query billSalesStats($fromDate: DateTime!, $toDate: DateTime!) {
    billSalesStats(fromDate: $fromDate, toDate: $toDate) {
      avgSales
      movie {
        avgSales
        date
        totalSales
      }
      fnb {
        avgSales
        date
        totalSales
      }
      overall {
        avgSales
        totalSales
        date
      }
      totalSales
    }
  }
`;

export const TRANSACTIONS_OVERVIEW_QUERY = gql`
  query billTransactionStats($fromDate: DateTime!, $toDate: DateTime!) {
    billTransactionStats(fromDate: $fromDate, toDate: $toDate) {
      totalTransactions
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
        }
      }
    }
  }
`;
