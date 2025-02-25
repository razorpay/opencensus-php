import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLMerchantPaymentAcceptanceChannelsResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantPaymentAcceptanceChannels'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantPaymentAcceptanceChannels'],
> = {
  android?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantAcceptanceChannel'], ParentType, ContextType>;
  ios?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantAcceptanceChannel'], ParentType, ContextType>;
  offlineStore?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantAcceptanceChannel'], ParentType, ContextType>;
  others?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantAcceptanceChannel'], ParentType, ContextType>;
  socialMedia?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantAcceptanceChannel'], ParentType, ContextType>;
  websites?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantAcceptanceChannel'], ParentType, ContextType>;
  whatsappSmsEmail?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantAcceptanceChannelWhatsappSmsEmail'],
    ParentType,
    ContextType
  >;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};