import { DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLMerchantVerificationDetailMetadata, DashboardGraphQLMerchantVerificationStatusEnum } from './index';
export type DashboardGraphQLMerchantVerificationDetail = {
  __typename?: 'DashboardGraphQLMerchantVerificationDetail';
  artefactType: DashboardGraphQLScalars['String'];
  metadata?: DashboardGraphQLMaybe<DashboardGraphQLMerchantVerificationDetailMetadata>;
  status?: DashboardGraphQLMaybe<DashboardGraphQLMerchantVerificationStatusEnum>;
};