import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLSendCreatePayoutLinkOtp = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLSendCreatePayoutLinkOtp';
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
  token: DashboardGraphQLScalars['String'];
};