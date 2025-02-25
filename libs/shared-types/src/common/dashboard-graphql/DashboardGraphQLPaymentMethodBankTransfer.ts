import { DashboardGraphQLMoney, DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLPaymentPayerBankAccount, DashboardGraphQLPaymentVirtualAccount } from './index';
export type DashboardGraphQLPaymentMethodBankTransfer = {
  __typename?: 'DashboardGraphQLPaymentMethodBankTransfer';
  amount: DashboardGraphQLMoney;
  bankReference: DashboardGraphQLScalars['String'];
  id: DashboardGraphQLScalars['ID'];
  mode: DashboardGraphQLScalars['String'];
  payerBankAccount?: DashboardGraphQLMaybe<DashboardGraphQLPaymentPayerBankAccount>;
  virtualAccount: DashboardGraphQLPaymentVirtualAccount;
};