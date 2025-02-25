import { DashboardGraphQLMaybe, DashboardGraphQLOverViewResponseType, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLFailedPaymentsOverviewSuccessResponse = {
  __typename?: 'DashboardGraphQLFailedPaymentsOverviewSuccessResponse';
  bank: Array<DashboardGraphQLMaybe<DashboardGraphQLOverViewResponseType>>;
  business: Array<DashboardGraphQLMaybe<DashboardGraphQLOverViewResponseType>>;
  code: DashboardGraphQLScalars['PositiveInt'];
  customer: Array<DashboardGraphQLMaybe<DashboardGraphQLOverViewResponseType>>;
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  others: Array<DashboardGraphQLMaybe<DashboardGraphQLOverViewResponseType>>;
  success: DashboardGraphQLScalars['Boolean'];
};