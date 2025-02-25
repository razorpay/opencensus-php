import { DashboardGraphQLMoneyInput, DashboardGraphQLScalars, DashboardGraphQLPayoutModeEnum, DashboardGraphQLInputMaybe } from './index';
export type DashboardGraphQLMutationVendorPaymentPayoutCreateArgs = {
  amount: DashboardGraphQLMoneyInput;
  bankingAccountNumber: DashboardGraphQLScalars['String'];
  fundAccountId: DashboardGraphQLScalars['String'];
  mode: DashboardGraphQLPayoutModeEnum;
  narration?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  otp: DashboardGraphQLScalars['String'];
  purpose: DashboardGraphQLScalars['String'];
  queueOnLowBalance: DashboardGraphQLScalars['Boolean'];
  token: DashboardGraphQLScalars['String'];
  vendorPaymentId: DashboardGraphQLScalars['ID'];
};