import { DashboardGraphQLMoneyInput, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMutationSendPayoutApproveBulkOtpArgs = {
  amount: DashboardGraphQLMoneyInput;
  bankingAccountNumber: DashboardGraphQLScalars['String'];
  count: DashboardGraphQLScalars['Int'];
};