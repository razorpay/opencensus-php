import { DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLPpTrackingSettings = {
  __typename?: 'PPTrackingSettings';
  ppFbEventAddToCartEnabled?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  ppFbEventInitiatePaymentEnabled?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  ppFbEventPaymentCompleteEnabled?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  ppFbPixelTrackingId?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  ppGaPixelTrackingId?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
};