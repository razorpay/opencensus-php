import { DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLMerchantPaymentHandleAvailabilitySuccessResponse = {
  __typename?: 'DashboardGraphQLMerchantPaymentHandleAvailabilitySuccessResponse';
  /** Similar to HTTP status code, represents the status of the query */
  code: DashboardGraphQLScalars['PositiveInt'];
  /** Indicates whether payment handle is available or not */
  isPaymentHandleAvailable: DashboardGraphQLScalars['Boolean'];
  /** Human-readable error or success message for the UI */
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  /** Indicates whether request was successfull or not */
  success: DashboardGraphQLScalars['Boolean'];
};