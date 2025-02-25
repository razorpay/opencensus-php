import { DashboardGraphQLScalars } from './index';
export type DashboardGraphQLQueryMerchantContactFundAccountsArgs = {
  contactId: DashboardGraphQLScalars['ID'];
  limit: DashboardGraphQLScalars['PositiveInt'];
  offset: DashboardGraphQLScalars['NonNegativeInt'];
};