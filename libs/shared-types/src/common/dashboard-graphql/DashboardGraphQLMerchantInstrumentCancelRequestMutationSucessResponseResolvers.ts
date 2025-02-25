import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLMerchantInstrumentCancelRequestMutationSucessResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantInstrumentCancelRequestMutationSucessResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantInstrumentCancelRequestMutationSucessResponse'],
> = {
  code?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['PositiveInt'], ParentType, ContextType>;
  merchantInstrument?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantInstrument'], ParentType, ContextType>;
  message?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['String'], ParentType, ContextType>;
  success?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};