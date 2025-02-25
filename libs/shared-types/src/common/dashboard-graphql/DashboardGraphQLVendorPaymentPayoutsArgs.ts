import { DashboardGraphQLInputMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLVendorPaymentPayoutsArgs = {
  limit?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['PositiveInt']>;
  offset?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['NonNegativeInt']>;
};