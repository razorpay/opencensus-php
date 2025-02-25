import { DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLGoalTrackerMetaData = {
  __typename?: 'DashboardGraphQLGoalTrackerMetaData';
  availableUnits?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Int']>;
  collectedAmount?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Int']>;
  displayAvailableUnits?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Int']>;
  displayDaysLeft?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Int']>;
  displaySoldUnits?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Int']>;
  displaySupporterCount?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Int']>;
  goalAmount?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Int']>;
  goalEndTimestamp?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  goalEndTimestampFormatted?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Int']>;
  soldUnits?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Int']>;
  supporterCount?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Int']>;
};