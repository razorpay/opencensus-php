import { DashboardGraphQLInputMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLQueryMerchantBankingAccountsBalanceArgs = {
  cached?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['Boolean']>;
  id?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['ID']>;
  type: DashboardGraphQLScalars['String'];
};