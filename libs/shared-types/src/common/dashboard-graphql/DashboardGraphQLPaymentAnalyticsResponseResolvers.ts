import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLMaybe, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLPaymentAnalyticsResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLPaymentAnalyticsResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLPaymentAnalyticsResponse'],
> = {
  aggregatedBy?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLPaymentAnalyticsAggregateByEnum'],
    ParentType,
    ContextType
  >;
  analytics?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<Array<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLPaymentAnalytics']>>>,
    ParentType,
    ContextType
  >;
  interval?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLPaymentAnalyticsIntervalEnum']>,
    ParentType,
    ContextType
  >;
  updatedAt?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DateTime'], ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};