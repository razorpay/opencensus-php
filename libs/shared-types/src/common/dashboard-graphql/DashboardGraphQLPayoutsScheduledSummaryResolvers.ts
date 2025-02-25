import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLPayoutsScheduledSummaryResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLPayoutsScheduledSummary'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLPayoutsScheduledSummary'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    | 'DashboardGraphQLPayoutsScheduledSummaryAllTime'
    | 'DashboardGraphQLPayoutsScheduledSummaryNextMonth'
    | 'DashboardGraphQLPayoutsScheduledSummaryNextTwoDays'
    | 'DashboardGraphQLPayoutsScheduledSummaryNextWeek'
    | 'DashboardGraphQLPayoutsScheduledSummaryToday',
    ParentType,
    ContextType
  >;
};