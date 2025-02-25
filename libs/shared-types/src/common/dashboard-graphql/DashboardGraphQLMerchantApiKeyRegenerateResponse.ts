import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLMerchantApiKeyRegenerateNew, DashboardGraphQLMerchantApiKeyRegenerateOld } from './index';
export type DashboardGraphQLMerchantApiKeyRegenerateResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLMerchantApiKeyRegenerateResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  newApiKey: DashboardGraphQLMerchantApiKeyRegenerateNew;
  oldApiKey: DashboardGraphQLMerchantApiKeyRegenerateOld;
  success: DashboardGraphQLScalars['Boolean'];
};