import { DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMutationSetNewPasswordArgs = {
  email: DashboardGraphQLScalars['String'];
  password: DashboardGraphQLScalars['String'];
  password_confirmation: DashboardGraphQLScalars['String'];
  token: DashboardGraphQLScalars['String'];
};