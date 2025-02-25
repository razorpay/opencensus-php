import { DashboardGraphQLMerchantBusinessCategories, DashboardGraphQLScalars, DashboardGraphQLMerchantBusinessSubCategory } from './index';
export type DashboardGraphQLMerchantBusinessCategoriesResponse = DashboardGraphQLMerchantBusinessCategories & {
  __typename?: 'DashboardGraphQLMerchantBusinessCategoriesResponse';
  categoryName: DashboardGraphQLScalars['String'];
  categoryValue: DashboardGraphQLScalars['String'];
  subCategories: Array<DashboardGraphQLMerchantBusinessSubCategory>;
};