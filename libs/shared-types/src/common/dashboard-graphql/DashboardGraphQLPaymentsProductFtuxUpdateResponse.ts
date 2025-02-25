import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLPaymentsProductFtuxUpdateResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLPaymentsProductFtuxUpdateResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};