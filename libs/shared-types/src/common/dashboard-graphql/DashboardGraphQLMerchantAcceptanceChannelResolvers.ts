import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLMaybe, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLMerchantAcceptanceChannelResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantAcceptanceChannel'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantAcceptanceChannel'],
> = {
  accept?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['Boolean'], ParentType, ContextType>;
  complianceConsent?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['Boolean']>, ParentType, ContextType>;
  socialMediaUrls?: DashboardGraphQLResolver<
    Array<DashboardGraphQLResolversTypes['MerchantSocialMediaURLField']>,
    ParentType,
    ContextType
  >;
  urls?: DashboardGraphQLResolver<Array<DashboardGraphQLResolversTypes['MerchantURLField']>, ParentType, ContextType>;
  value?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};