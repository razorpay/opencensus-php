import { DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLPaymentInstantRefundEligibilityOptionEnum, DashboardGraphQLPaymentInstantRefundEligibilityAmount } from './index';
export type DashboardGraphQLPaymentInstantRefundEligibilityResponse = {
  __typename?: 'DashboardGraphQLPaymentInstantRefundEligibilityResponse';
  isAllowed: DashboardGraphQLScalars['Boolean'];
  messages?: DashboardGraphQLMaybe<DashboardGraphQLScalars['JSONObject']>;
  option: DashboardGraphQLPaymentInstantRefundEligibilityOptionEnum;
  refund?: DashboardGraphQLMaybe<DashboardGraphQLPaymentInstantRefundEligibilityAmount>;
};