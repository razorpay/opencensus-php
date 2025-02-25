import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLVendorPayment } from './index';
export type DashboardGraphQLVendorPaymentPayoutCreateResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLVendorPaymentPayoutCreateResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
  vendorPayment?: DashboardGraphQLMaybe<DashboardGraphQLVendorPayment>;
};