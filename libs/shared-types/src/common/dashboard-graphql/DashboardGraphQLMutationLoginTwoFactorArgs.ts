import { DashboardGraphQLScalars, DashboardGraphQLInputMaybe } from './index';
export type DashboardGraphQLMutationLoginTwoFactorArgs = {
  code: DashboardGraphQLScalars['String'];
  oAuthToken?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
};