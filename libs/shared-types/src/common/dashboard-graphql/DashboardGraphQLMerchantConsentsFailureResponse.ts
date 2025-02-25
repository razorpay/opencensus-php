import { DashboardGraphQLScalars, DashboardGraphQLMerchantConsentsErrorTypeEnum, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLMerchantConsentsFailureResponse = {
  __typename?: 'DashboardGraphQLMerchantConsentsFailureResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  errorCode: DashboardGraphQLMerchantConsentsErrorTypeEnum;
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};