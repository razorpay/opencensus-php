import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLPaymentsNewLaunchProductViewUpdate = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLPaymentsNewLaunchProductViewUpdate';
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};