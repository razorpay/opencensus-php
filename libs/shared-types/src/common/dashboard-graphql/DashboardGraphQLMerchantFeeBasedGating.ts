import { DashboardGraphQLMaybe, DashboardGraphQLScalars, DashboardGraphQLFeeBasedGatingPaymentStatusEnum } from './index';
export type DashboardGraphQLMerchantFeeBasedGating = {
  __typename?: 'DashboardGraphQLMerchantFeeBasedGating';
  amount?: DashboardGraphQLMaybe<DashboardGraphQLScalars['BigInt']>;
  isEligible: DashboardGraphQLScalars['Boolean'];
  orderId?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  paymentStatus?: DashboardGraphQLMaybe<DashboardGraphQLFeeBasedGatingPaymentStatusEnum>;
};