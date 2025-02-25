import { DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLStepMeta = {
  __typename?: 'DashboardGraphQLStepMeta';
  description?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  template?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  title?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
};