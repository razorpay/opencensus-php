import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLSetNewPasswordEnum } from './index';
export type DashboardGraphQLSetNewPasswordErrorResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLSetNewPasswordErrorResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  errorCode?: DashboardGraphQLMaybe<DashboardGraphQLSetNewPasswordEnum>;
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};