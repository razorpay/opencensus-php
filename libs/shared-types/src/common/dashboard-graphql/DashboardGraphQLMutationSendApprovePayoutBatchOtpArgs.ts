import { DashboardGraphQLScalars, DashboardGraphQLMoneyInput } from './index';
export type DashboardGraphQLMutationSendApprovePayoutBatchOtpArgs = {
  bankingAccountNumber: DashboardGraphQLScalars['String'];
  totalAmount: DashboardGraphQLMoneyInput;
  totalCount: DashboardGraphQLScalars['PositiveInt'];
};