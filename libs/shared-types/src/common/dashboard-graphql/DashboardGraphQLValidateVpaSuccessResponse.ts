import { DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLValidateVpaSuccessResponse = {
  __typename?: 'DashboardGraphQLValidateVpaSuccessResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  customerName: DashboardGraphQLScalars['String'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
  vpa: DashboardGraphQLScalars['String'];
};