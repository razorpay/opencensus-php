import { DashboardGraphQLMoneyInput, DashboardGraphQLInputMaybe, DashboardGraphQLScalars, DashboardGraphQLPaymentRefundSpeedRequestedEnum } from './index';
export type DashboardGraphQLMutationPaymentRefundArgs = {
  amount: DashboardGraphQLMoneyInput;
  comment?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  id: DashboardGraphQLScalars['ID'];
  speed?: DashboardGraphQLInputMaybe<DashboardGraphQLPaymentRefundSpeedRequestedEnum>;
};