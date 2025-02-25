import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLResendEmailOtp = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLResendEmailOtp';
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
  token: DashboardGraphQLScalars['String'];
};