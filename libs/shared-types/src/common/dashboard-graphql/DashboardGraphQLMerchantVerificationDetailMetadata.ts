import { DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMerchantVerificationDetailMetadata = {
  __typename?: 'DashboardGraphQLMerchantVerificationDetailMetadata';
  category?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  confidenceScore?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Float']>;
  subcategory?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
};