import { DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMerchantReferralFailureResponse = {
  __typename?: 'DashboardGraphQLMerchantReferralFailureResponse';
  /** Similar to HTTP status code, represents the status of the query */
  code: DashboardGraphQLScalars['PositiveInt'];
  /** Human-readable error message for the UI */
  message: DashboardGraphQLScalars['String'];
};