import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLUserExistsByEmailOrPhoneSuccess = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLUserExistsByEmailOrPhoneSuccess';
  code: DashboardGraphQLScalars['PositiveInt'];
  isPasswordSet?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Boolean']>;
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
  userExists: DashboardGraphQLScalars['Boolean'];
};