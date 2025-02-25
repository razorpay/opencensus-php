import { DashboardGraphQLInputMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMutationLoginTwoFactorPasswordArgs = {
  oAuthToken?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  password: DashboardGraphQLScalars['String'];
};