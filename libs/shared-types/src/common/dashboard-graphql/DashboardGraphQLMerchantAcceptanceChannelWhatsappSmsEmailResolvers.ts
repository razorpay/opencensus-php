import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLMerchantAcceptanceChannelWhatsappSmsEmailResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantAcceptanceChannelWhatsappSmsEmail'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantAcceptanceChannelWhatsappSmsEmail'],
> = {
  accept?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};