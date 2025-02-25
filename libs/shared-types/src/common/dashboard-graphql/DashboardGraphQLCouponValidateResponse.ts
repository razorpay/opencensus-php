import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLCouponValidateResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLCouponValidateResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  credits?: DashboardGraphQLMaybe<DashboardGraphQLScalars['BigInt']>;
  expiryDays?: DashboardGraphQLMaybe<DashboardGraphQLScalars['PositiveInt']>;
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};