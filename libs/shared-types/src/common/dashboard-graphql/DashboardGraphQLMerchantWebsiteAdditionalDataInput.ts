import { DashboardGraphQLInputMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMerchantWebsiteAdditionalDataInput = {
  contactEmail?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['EmailAddress']>;
  contactSupportNumber?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  refundProcessPeriod?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  refundRequestPeriod?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  shippingPeriod?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
};