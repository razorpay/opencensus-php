import { DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMerchantWebsiteAdditionalData = {
  __typename?: 'DashboardGraphQLMerchantWebsiteAdditionalData';
  contactEmail?: DashboardGraphQLMaybe<DashboardGraphQLScalars['EmailAddress']>;
  contactSupportNumber?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  refundProcessPeriod?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  refundRequestPeriod?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  shippingPeriod?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
};