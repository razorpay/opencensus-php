import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLAuthSourceEnum, DashboardGraphQLUser } from './index';
export type DashboardGraphQLAuthUser = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLAuthUser';
  code: DashboardGraphQLScalars['PositiveInt'];
  message: DashboardGraphQLScalars['String'];
  source?: DashboardGraphQLMaybe<DashboardGraphQLAuthSourceEnum>;
  success: DashboardGraphQLScalars['Boolean'];
  user: DashboardGraphQLUser;
};