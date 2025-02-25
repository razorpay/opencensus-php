import { DashboardGraphQLScalars, DashboardGraphQLMerchantBusinessTypeEnum } from './index';
export type DashboardGraphQLBusinessType = {
  __typename?: 'DashboardGraphQLBusinessType';
  label: DashboardGraphQLScalars['String'];
  status: DashboardGraphQLScalars['String'];
  value: DashboardGraphQLMerchantBusinessTypeEnum;
};