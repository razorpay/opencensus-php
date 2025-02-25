import { DashboardGraphQLMerchantPolicyEmptyResponse, DashboardGraphQLMerchantPolicyFailureResponse, DashboardGraphQLMerchantPolicySuccessResponse } from './index';
export type DashboardGraphQLMerchantPolicyResponse =
  | DashboardGraphQLMerchantPolicyEmptyResponse
  | DashboardGraphQLMerchantPolicyFailureResponse
  | DashboardGraphQLMerchantPolicySuccessResponse;