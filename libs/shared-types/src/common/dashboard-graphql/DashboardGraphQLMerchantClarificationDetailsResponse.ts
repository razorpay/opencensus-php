import { DashboardGraphQLMerchantClarificationDetail, DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLMerchantClarificationDetailsResponse = {
  __typename?: 'DashboardGraphQLMerchantClarificationDetailsResponse';
  clarificationDetails: DashboardGraphQLMerchantClarificationDetail;
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};