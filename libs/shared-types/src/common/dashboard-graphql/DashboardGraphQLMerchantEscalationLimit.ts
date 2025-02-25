import { DashboardGraphQLMerchantActivationMilestoneEnum, DashboardGraphQLMoney } from './index';
export type DashboardGraphQLMerchantEscalationLimit = {
  __typename?: 'DashboardGraphQLMerchantEscalationLimit';
  milestone: DashboardGraphQLMerchantActivationMilestoneEnum;
  threshold: DashboardGraphQLMoney;
};