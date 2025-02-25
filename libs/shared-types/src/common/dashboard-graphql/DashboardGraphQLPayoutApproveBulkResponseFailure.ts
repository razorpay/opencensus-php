import { DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLPayoutApproveBulkResponseFailure = {
  __typename?: 'DashboardGraphQLPayoutApproveBulkResponseFailure';
  code: DashboardGraphQLScalars['PositiveInt'];
  failedPayoutIds?: DashboardGraphQLMaybe<Array<DashboardGraphQLScalars['ID']>>;
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};