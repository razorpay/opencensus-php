import { DashboardGraphQLMaybe, DashboardGraphQLScalars, DashboardGraphQLGoalTrackerMetaData } from './index';
export type DashboardGraphQLGoalTrackerSettings = {
  __typename?: 'DashboardGraphQLGoalTrackerSettings';
  isActive?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Int']>;
  metaData?: DashboardGraphQLMaybe<DashboardGraphQLGoalTrackerMetaData>;
  trackerType?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
};