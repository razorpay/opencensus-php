import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLMerchantStore } from './index';
export type DashboardGraphQLMerchantStoreActivateSuccessResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLMerchantStoreActivateSuccessResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  store: DashboardGraphQLMerchantStore;
  success: DashboardGraphQLScalars['Boolean'];
};