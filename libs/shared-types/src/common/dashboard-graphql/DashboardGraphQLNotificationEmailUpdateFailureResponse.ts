import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLNotificationEmailUpdateFailureResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLNotificationEmailUpdateFailureResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};