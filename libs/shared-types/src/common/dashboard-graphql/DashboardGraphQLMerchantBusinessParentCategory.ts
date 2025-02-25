import { DashboardGraphQLMerchantBusinessCategoriesResponse, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMerchantBusinessParentCategory = {
  __typename?: 'DashboardGraphQLMerchantBusinessParentCategory';
  categories: Array<DashboardGraphQLMerchantBusinessCategoriesResponse>;
  parentCategoryName: DashboardGraphQLScalars['String'];
  parentCategoryValue: DashboardGraphQLScalars['String'];
};