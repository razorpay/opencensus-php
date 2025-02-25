import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLMerchantStore } from './index';
export type DashboardGraphQLMerchantStoreUpdateSuccessResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLMerchantStoreUpdateSuccessResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  store: DashboardGraphQLMerchantStore;
  success: DashboardGraphQLScalars['Boolean'];
};