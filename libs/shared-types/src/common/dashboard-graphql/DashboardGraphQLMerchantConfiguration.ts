import { DashboardGraphQLMaybe, DashboardGraphQLPaymentRefundSpeedRequestedEnum, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMerchantConfiguration = {
  __typename?: 'DashboardGraphQLMerchantConfiguration';
  defaultRefundSpeed?: DashboardGraphQLMaybe<DashboardGraphQLPaymentRefundSpeedRequestedEnum>;
  transactionReportEmail?: DashboardGraphQLMaybe<Array<DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>>>;
};