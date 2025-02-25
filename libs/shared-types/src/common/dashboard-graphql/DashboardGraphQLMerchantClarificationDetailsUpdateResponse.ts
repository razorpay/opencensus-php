import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLMerchantClarificationDetail, DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLMerchantClarificationDetailsUpdateResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLMerchantClarificationDetailsUpdateResponse';
  clarificationDetails: DashboardGraphQLMerchantClarificationDetail;
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};