import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLMaybe, DashboardGraphQLResolversTypes, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLAcceptPaymentsWidgetResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLAcceptPaymentsWidget'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLAcceptPaymentsWidget'],
> = {
  products?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<Array<DashboardGraphQLResolversTypes['DashboardGraphQLAcceptPaymentsProduct']>>,
    ParentType,
    ContextType
  >;
  title?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['String'], ParentType, ContextType>;
  type?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLPaymentsWidgetTypeEnum'], ParentType, ContextType>;
  variant?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLWidgetVariantEnum'], ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};