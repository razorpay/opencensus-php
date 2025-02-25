import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLPayoutsQueuedSummaryResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLPayoutsQueuedSummary'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLPayoutsQueuedSummary'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    | 'DashboardGraphQLPayoutsQueuedSummaryBeneficiaryBankDown'
    | 'DashboardGraphQLPayoutsQueuedSummaryLowBalance'
    | 'PayoutsQueuedSummaryNEFTLimitExhausted'
    | 'PayoutsQueuedSummaryNEFTWindowClosed'
    | 'PayoutsQueuedSummaryNPCISystemDown'
    | 'DashboardGraphQLPayoutsQueuedSummaryWithoutReason',
    ParentType,
    ContextType
  >;
};