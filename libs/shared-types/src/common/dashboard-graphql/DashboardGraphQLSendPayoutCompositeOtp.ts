import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLSendPayoutCompositeOtp = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLSendPayoutCompositeOtp';
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
  token?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
};