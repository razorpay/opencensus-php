import { DashboardGraphQLMoney, DashboardGraphQLMerchantEscalationLimit, DashboardGraphQLMaybe, DashboardGraphQLMerchantEscalationAction, DashboardGraphQLMerchantEscalationTypeEnum, DashboardGraphQLScalars, DashboardGraphQLMerchantTransactionLimit } from './index';
export type DashboardGraphQLMerchantActivationEscalationsBreached = {
  __typename?: 'DashboardGraphQLMerchantActivationEscalationsBreached';
  amount: DashboardGraphQLMoney;
  currentEscalationLimit: DashboardGraphQLMerchantEscalationLimit;
  escalationAction?: DashboardGraphQLMaybe<DashboardGraphQLMerchantEscalationAction>;
  escalationType: DashboardGraphQLMerchantEscalationTypeEnum;
  id: DashboardGraphQLScalars['ID'];
  nextEscalationLimit?: DashboardGraphQLMaybe<DashboardGraphQLMerchantEscalationLimit>;
  transactionLimit: DashboardGraphQLMerchantTransactionLimit;
};