import { DashboardGraphQLMaybe, DashboardGraphQLPaymentRefundSpeedProcessedEnum, DashboardGraphQLPaymentRefundSpeedRequestedEnum } from './index';
export type DashboardGraphQLPaymentRefundSpeed = {
  __typename?: 'DashboardGraphQLPaymentRefundSpeed';
  processed?: DashboardGraphQLMaybe<DashboardGraphQLPaymentRefundSpeedProcessedEnum>;
  requested?: DashboardGraphQLMaybe<DashboardGraphQLPaymentRefundSpeedRequestedEnum>;
};