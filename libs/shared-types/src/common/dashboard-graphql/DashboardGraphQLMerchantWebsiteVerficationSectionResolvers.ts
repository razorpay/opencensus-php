import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLMerchantWebsiteVerficationSectionResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantWebsiteVerficationSection'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantWebsiteVerficationSection'],
> = {
  systemApproved?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['Boolean'], ParentType, ContextType>;
  url?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['URL'], ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};