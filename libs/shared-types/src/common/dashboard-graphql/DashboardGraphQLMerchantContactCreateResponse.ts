import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLMerchantContact } from './index';
export type DashboardGraphQLMerchantContactCreateResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLMerchantContactCreateResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  merchantContact?: DashboardGraphQLMaybe<DashboardGraphQLMerchantContact>;
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};