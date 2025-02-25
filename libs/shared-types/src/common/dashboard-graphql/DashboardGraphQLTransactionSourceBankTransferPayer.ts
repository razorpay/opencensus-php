import { DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLTransactionSourceBankTransferPayer = {
  __typename?: 'DashboardGraphQLTransactionSourceBankTransferPayer';
  accountNumber: DashboardGraphQLScalars['String'];
  ifsc: DashboardGraphQLScalars['String'];
  name?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
};