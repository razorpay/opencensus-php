import { DashboardGraphQLMoney, DashboardGraphQLScalars, DashboardGraphQLTransactionSource, DashboardGraphQLTransactionTypeEnum } from './index';
export type DashboardGraphQLTransaction = {
  __typename?: 'DashboardGraphQLTransaction';
  amount: DashboardGraphQLMoney;
  balance: DashboardGraphQLMoney;
  bankingAccountNumber: DashboardGraphQLScalars['String'];
  createdAt: DashboardGraphQLScalars['DateTime'];
  id: DashboardGraphQLScalars['ID'];
  source: DashboardGraphQLTransactionSource;
  type: DashboardGraphQLTransactionTypeEnum;
};