import { DashboardGraphQLMoneyInput, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMutationSendPayoutCompositeOtpArgs = {
  amount: DashboardGraphQLMoneyInput;
  bankingAccountNumber: DashboardGraphQLScalars['String'];
  vpa: DashboardGraphQLScalars['VPA'];
};