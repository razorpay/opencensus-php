import { DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLMerchantPolicySuccessResponse = {
  __typename?: 'DashboardGraphQLMerchantPolicySuccessResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  policy: DashboardGraphQLScalars['String'];
  success: DashboardGraphQLScalars['Boolean'];
};