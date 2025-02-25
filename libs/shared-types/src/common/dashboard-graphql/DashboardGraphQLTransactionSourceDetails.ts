import { DashboardGraphQLMoney, DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLTransactionSourceDetails = {
  __typename?: 'DashboardGraphQLTransactionSourceDetails';
  amount: DashboardGraphQLMoney;
  createdAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  fee?: DashboardGraphQLMaybe<DashboardGraphQLMoney>;
  id: DashboardGraphQLScalars['ID'];
  isInternational?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Boolean']>;
  status?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  tax?: DashboardGraphQLMaybe<DashboardGraphQLMoney>;
};