import { DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLMerchantEmailUpdateSuccessResponse = {
  __typename?: 'DashboardGraphQLMerchantEmailUpdateSuccessResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  isExistingUser: DashboardGraphQLScalars['Boolean'];
  isOwner: DashboardGraphQLScalars['Boolean'];
  isTeamMember: DashboardGraphQLScalars['Boolean'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};