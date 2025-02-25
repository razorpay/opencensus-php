import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMerchantContactFundAccount, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLMerchantContactFundAccountCreateResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLMerchantContactFundAccountCreateResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  fundAccount: DashboardGraphQLMerchantContactFundAccount;
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};