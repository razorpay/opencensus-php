import { gql } from 'graphql-tag';

export const BILL_ME_COMPANY_BALANCE_QUERY = gql`
  query BillWalletBalance {
    billWalletBalance {
      balance
    }
  }
`;
