import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLMerchantStore } from './index';
export type DashboardGraphQLMerchantStoreDeactivateSuccessResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLMerchantStoreDeactivateSuccessResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  store: DashboardGraphQLMerchantStore;
  success: DashboardGraphQLScalars['Boolean'];
};