import { DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLResetPasswordEmail = {
  __typename?: 'DashboardGraphQLResetPasswordEmail';
  emailSent: DashboardGraphQLScalars['Boolean'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
};