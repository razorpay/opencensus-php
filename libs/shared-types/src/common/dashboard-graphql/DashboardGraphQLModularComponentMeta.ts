import { DashboardGraphQLMaybe, DashboardGraphQLScalars, DashboardGraphQLModularComponentMetaUi, DashboardGraphQLTooltipBadge } from './index';
export type DashboardGraphQLModularComponentMeta = {
  __typename?: 'DashboardGraphQLModularComponentMeta';
  description?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  errorCode?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  metaUi?: DashboardGraphQLMaybe<DashboardGraphQLModularComponentMetaUi>;
  template?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  title?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  tooltipBadge?: DashboardGraphQLMaybe<DashboardGraphQLTooltipBadge>;
};