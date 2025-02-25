import { DashboardGraphQLMoneyInput, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMutationSendApprovePayoutOtpArgs = {
  amount: DashboardGraphQLMoneyInput;
  bankingAccountNumber: DashboardGraphQLScalars['String'];
  payoutId: DashboardGraphQLScalars['ID'];
};