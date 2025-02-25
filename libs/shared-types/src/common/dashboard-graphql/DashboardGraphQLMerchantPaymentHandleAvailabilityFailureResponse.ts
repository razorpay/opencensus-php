import { DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLMerchantPaymentHandleAvailabilityFailureResponse = {
  __typename?: 'DashboardGraphQLMerchantPaymentHandleAvailabilityFailureResponse';
  /** Similar to HTTP status code, represents the status of the query */
  code: DashboardGraphQLScalars['PositiveInt'];
  /** Human-readable error or success message for the UI */
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  /** Indicates whether request was successfull or not */
  success: DashboardGraphQLScalars['Boolean'];
};