import { DashboardGraphQLBusinessType } from './index';
export type DashboardGraphQLMerchantBusinessTypesResponse = {
  __typename?: 'DashboardGraphQLMerchantBusinessTypesResponse';
  registered: Array<DashboardGraphQLBusinessType>;
  unregistered: Array<DashboardGraphQLBusinessType>;
};