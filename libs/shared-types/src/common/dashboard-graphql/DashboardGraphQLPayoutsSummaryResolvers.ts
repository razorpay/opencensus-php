import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLPayoutsSummaryResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLPayoutsSummary'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLPayoutsSummary'],
> = {
  bankingAccount?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantBankingAccount'], ParentType, ContextType>;
  pending?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLPayoutsPendingSummary'], ParentType, ContextType>;
  queued?: DashboardGraphQLResolver<Array<DashboardGraphQLResolversTypes['DashboardGraphQLPayoutsQueuedSummary']>, ParentType, ContextType>;
  scheduled?: DashboardGraphQLResolver<Array<DashboardGraphQLResolversTypes['DashboardGraphQLPayoutsScheduledSummary']>, ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};