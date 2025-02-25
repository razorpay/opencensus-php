import { DashboardGraphQLMoneyInput, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMutationSendCreatePayoutOtpArgs = {
  amount: DashboardGraphQLMoneyInput;
  bankingAccountNumber: DashboardGraphQLScalars['String'];
  fundAccountId: DashboardGraphQLScalars['String'];
  purpose: DashboardGraphQLScalars['String'];
};