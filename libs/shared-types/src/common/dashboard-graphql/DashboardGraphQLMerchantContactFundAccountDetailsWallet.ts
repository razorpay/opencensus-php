import { DashboardGraphQLMaybe, DashboardGraphQLScalars, DashboardGraphQLPhone } from './index';
export type DashboardGraphQLMerchantContactFundAccountDetailsWallet = {
  __typename?: 'DashboardGraphQLMerchantContactFundAccountDetailsWallet';
  email?: DashboardGraphQLMaybe<DashboardGraphQLScalars['EmailAddress']>;
  name?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  phone: DashboardGraphQLPhone;
  provider: DashboardGraphQLScalars['String'];
};