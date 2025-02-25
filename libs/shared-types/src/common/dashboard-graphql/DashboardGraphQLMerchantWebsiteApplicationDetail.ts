import { DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMerchantWebsiteApplicationDetail = {
  __typename?: 'DashboardGraphQLMerchantWebsiteApplicationDetail';
  documentId?: DashboardGraphQLMaybe<DashboardGraphQLScalars['ID']>;
  fileStoreId?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  host: DashboardGraphQLScalars['URL'];
  signedUrl?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  systemApproved?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Boolean']>;
  url?: DashboardGraphQLMaybe<DashboardGraphQLScalars['URL']>;
};