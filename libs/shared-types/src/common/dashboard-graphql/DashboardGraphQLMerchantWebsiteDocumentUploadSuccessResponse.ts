import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMerchantWebsite, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLMerchantWebsiteDocumentUploadSuccessResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLMerchantWebsiteDocumentUploadSuccessResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  merchantWebsite: DashboardGraphQLMerchantWebsite;
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};