import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMerchantWebsite, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLMerchantWebsiteDocumentDeleteSuccessResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLMerchantWebsiteDocumentDeleteSuccessResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  merchantWebsite: DashboardGraphQLMerchantWebsite;
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};