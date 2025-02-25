import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLMaybe, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLPaymentPageDateResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLPaymentPageDate'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLPaymentPageDate'],
> = {
  createdAt?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DateTime'], ParentType, ContextType>;
  expireBy?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DateTime']>, ParentType, ContextType>;
  updatedAt?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DateTime']>, ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};