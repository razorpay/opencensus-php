import { DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMerchantContactFundAccountDetailsBankAccount = {
  __typename?: 'DashboardGraphQLMerchantContactFundAccountDetailsBankAccount';
  bankName?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  holderName: DashboardGraphQLScalars['String'];
  ifsc: DashboardGraphQLScalars['String'];
  number: DashboardGraphQLScalars['String'];
};