import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLMerchantActivationEscalationsNotBreachedResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantActivationEscalationsNotBreached'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantActivationEscalationsNotBreached'],
> = {
  transactionLimit?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantTransactionLimit'], ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};