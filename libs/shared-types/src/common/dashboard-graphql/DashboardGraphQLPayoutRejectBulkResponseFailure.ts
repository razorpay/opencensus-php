import { DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLPayoutRejectBulkResponseFailure = {
  __typename?: 'DashboardGraphQLPayoutRejectBulkResponseFailure';
  code: DashboardGraphQLScalars['PositiveInt'];
  failedPayoutIds?: DashboardGraphQLMaybe<Array<DashboardGraphQLScalars['ID']>>;
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};