import { DashboardGraphQLScalars, DashboardGraphQLMerchantBusinessSubCategory } from './index';
export type DashboardGraphQLMerchantBusinessCategories = {
  categoryName: DashboardGraphQLScalars['String'];
  categoryValue: DashboardGraphQLScalars['String'];
  subCategories: Array<DashboardGraphQLMerchantBusinessSubCategory>;
};