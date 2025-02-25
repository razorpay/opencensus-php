import { DashboardGraphQLMaybe, DashboardGraphQLScalars, DashboardGraphQLCheckoutOptions, DashboardGraphQLGoalTrackerSettings, DashboardGraphQLPartnerWebhookSettings, DashboardGraphQLPpTrackingSettings } from './index';
export type DashboardGraphQLPaymentPageSettings = {
  __typename?: 'DashboardGraphQLPaymentPageSettings';
  allowSocialShare?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  checkoutOptions?: DashboardGraphQLMaybe<DashboardGraphQLCheckoutOptions>;
  enable80GDetails?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  enableCustomSerialNumber?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  enableReceipt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  goalTracker?: DashboardGraphQLMaybe<DashboardGraphQLGoalTrackerSettings>;
  partnerWebhookSettings?: DashboardGraphQLMaybe<DashboardGraphQLPartnerWebhookSettings>;
  paymentButtonLabel?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  paymentSuccessMessage?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  paymentSuccessRedirectURL?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  selectedUdfField?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  theme?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  trackingSettings?: DashboardGraphQLMaybe<DashboardGraphQLPpTrackingSettings>;
  udfSchema?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  version?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
};