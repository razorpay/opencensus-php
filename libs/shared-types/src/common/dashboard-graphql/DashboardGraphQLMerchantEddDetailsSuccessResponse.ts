import { DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLMerchantEddItemDetail, DashboardGraphQLMerchantEddStatusEnum } from './index';
export type DashboardGraphQLMerchantEddDetailsSuccessResponse = {
  __typename?: 'DashboardGraphQLMerchantEddDetailsSuccessResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  details: Array<DashboardGraphQLMaybe<DashboardGraphQLMerchantEddItemDetail>>;
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  status: DashboardGraphQLMerchantEddStatusEnum;
  success: DashboardGraphQLScalars['Boolean'];
};