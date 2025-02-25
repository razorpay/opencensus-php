import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLMaybe, DashboardGraphQLResolversTypes, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLCreateWithdrawalResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['createWithdrawalResponse'] = DashboardGraphQLResolversParentTypes['createWithdrawalResponse'],
> = {
  withdrawal?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLWithdrawal']>, ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};