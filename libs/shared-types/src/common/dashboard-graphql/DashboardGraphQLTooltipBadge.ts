import { DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLTooltipBadge = {
  __typename?: 'DashboardGraphQLTooltipBadge';
  badgeColor?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  badgeContent: DashboardGraphQLScalars['String'];
  badgeSize?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  tooltipContent: DashboardGraphQLScalars['String'];
  tooltipPlacement?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  tooltipTitle?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
};