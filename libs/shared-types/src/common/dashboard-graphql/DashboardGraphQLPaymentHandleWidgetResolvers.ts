import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLPaymentHandleWidgetResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLPaymentHandleWidget'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLPaymentHandleWidget'],
> = {
  description?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['String'], ParentType, ContextType>;
  paymentHandle?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantPaymentHandle'], ParentType, ContextType>;
  title?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['String'], ParentType, ContextType>;
  type?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLPaymentsWidgetTypeEnum'], ParentType, ContextType>;
  variant?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLWidgetVariantEnum'], ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};