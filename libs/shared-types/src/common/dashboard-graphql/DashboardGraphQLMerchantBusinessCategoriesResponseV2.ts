import { DashboardGraphQLMerchantBusinessCategories, DashboardGraphQLScalars, DashboardGraphQLMerchantBusinessCategoryIcon, DashboardGraphQLMerchantBusinessSubCategory } from './index';
export type DashboardGraphQLMerchantBusinessCategoriesResponseV2 = DashboardGraphQLMerchantBusinessCategories & {
  __typename?: 'DashboardGraphQLMerchantBusinessCategoriesResponseV2';
  categoryName: DashboardGraphQLScalars['String'];
  categoryValue: DashboardGraphQLScalars['String'];
  iconDetails: DashboardGraphQLMerchantBusinessCategoryIcon;
  subCategories: Array<DashboardGraphQLMerchantBusinessSubCategory>;
};