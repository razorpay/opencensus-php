import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLRegisterMerchantErrorEnum } from './index';
export type DashboardGraphQLRegisterMerchantResponseFailure = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLRegisterMerchantResponseFailure';
  code: DashboardGraphQLScalars['PositiveInt'];
  errorCode?: DashboardGraphQLMaybe<DashboardGraphQLRegisterMerchantErrorEnum>;
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};