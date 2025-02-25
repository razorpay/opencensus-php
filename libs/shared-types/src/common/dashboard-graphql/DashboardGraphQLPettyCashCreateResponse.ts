import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLPettyCash } from './index';
export type DashboardGraphQLPettyCashCreateResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLPettyCashCreateResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  pettyCash?: DashboardGraphQLMaybe<DashboardGraphQLPettyCash>;
  success: DashboardGraphQLScalars['Boolean'];
};