import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLMaybe, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLCurrencyResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLCurrency'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLCurrency'],
> = {
  code?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLCurrencyCodeEnum'], ParentType, ContextType>;
  name?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLCurrencyNameEnum']>, ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};