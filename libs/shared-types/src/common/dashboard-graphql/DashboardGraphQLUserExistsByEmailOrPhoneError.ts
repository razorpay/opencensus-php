import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLUserExistsByEmailOrPhoneEnum } from './index';
export type DashboardGraphQLUserExistsByEmailOrPhoneError = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLUserExistsByEmailOrPhoneError';
  code: DashboardGraphQLScalars['PositiveInt'];
  errorCode?: DashboardGraphQLMaybe<DashboardGraphQLUserExistsByEmailOrPhoneEnum>;
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};