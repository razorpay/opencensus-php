import { DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLMerchantWebsiteTermsAndConditions = {
  __typename?: 'DashboardGraphQLMerchantWebsiteTermsAndConditions';
  deliverableType: DashboardGraphQLScalars['String'];
  link: DashboardGraphQLScalars['String'];
  refundProcessPeriod: DashboardGraphQLScalars['String'];
  refundRequestPeriod: DashboardGraphQLScalars['String'];
  shippingPeriod?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  supportEmail?: DashboardGraphQLMaybe<DashboardGraphQLScalars['EmailAddress']>;
  warrantyPeriod?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
};