import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLSetEmailPasswordEnum } from './index';
export type DashboardGraphQLSetEmailPasswordErrorResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLSetEmailPasswordErrorResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  errorCode?: DashboardGraphQLMaybe<DashboardGraphQLSetEmailPasswordEnum>;
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};