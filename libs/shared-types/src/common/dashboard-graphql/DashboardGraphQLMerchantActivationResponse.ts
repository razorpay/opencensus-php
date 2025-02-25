import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMerchant, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLMerchantActivationResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLMerchantActivationResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  merchant: DashboardGraphQLMerchant;
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};