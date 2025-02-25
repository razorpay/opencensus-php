import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLSetEmailPasswordSuccessResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLSetEmailPasswordSuccessResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  contactMobile: DashboardGraphQLScalars['String'];
  email: DashboardGraphQLScalars['String'];
  id: DashboardGraphQLScalars['String'];
  loggedInVia: DashboardGraphQLScalars['String'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  name: DashboardGraphQLScalars['String'];
  success: DashboardGraphQLScalars['Boolean'];
  userId: DashboardGraphQLScalars['String'];
};