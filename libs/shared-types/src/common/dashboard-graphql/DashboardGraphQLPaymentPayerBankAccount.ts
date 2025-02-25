import { DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLPaymentPayerBankAccount = {
  __typename?: 'DashboardGraphQLPaymentPayerBankAccount';
  accountNumber: DashboardGraphQLScalars['BigInt'];
  bankName?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  id: DashboardGraphQLScalars['ID'];
  ifsc?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  name: DashboardGraphQLScalars['String'];
};