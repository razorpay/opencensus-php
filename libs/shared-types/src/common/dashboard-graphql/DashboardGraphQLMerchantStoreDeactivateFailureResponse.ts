import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLMerchantStoreDeactivateFailureResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLMerchantStoreDeactivateFailureResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};