import { DashboardGraphQLPaginationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMerchantContact, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLMerchantContactsResponse = DashboardGraphQLPaginationResponseInterface & {
  __typename?: 'DashboardGraphQLMerchantContactsResponse';
  hasMore: DashboardGraphQLScalars['Boolean'];
  limit: DashboardGraphQLScalars['PositiveInt'];
  merchantContacts: Array<DashboardGraphQLMerchantContact>;
  offset: DashboardGraphQLScalars['NonNegativeInt'];
  total?: DashboardGraphQLMaybe<DashboardGraphQLScalars['NonNegativeInt']>;
};