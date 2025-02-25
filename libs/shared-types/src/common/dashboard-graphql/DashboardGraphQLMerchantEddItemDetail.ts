import { DashboardGraphQLMerchantEddStatusEnum, DashboardGraphQLEddEligibilityEnum } from './index';
export type DashboardGraphQLMerchantEddItemDetail = {
  __typename?: 'DashboardGraphQLMerchantEddItemDetail';
  status: DashboardGraphQLMerchantEddStatusEnum;
  type: DashboardGraphQLEddEligibilityEnum;
};