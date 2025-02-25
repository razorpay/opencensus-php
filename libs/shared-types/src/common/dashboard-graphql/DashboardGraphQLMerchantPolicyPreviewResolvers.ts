import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLMerchantPolicyPreviewResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantPolicyPreview'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantPolicyPreview'],
> = {
  html?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['String'], ParentType, ContextType>;
  section?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantWebsiteSectionEnum'], ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};