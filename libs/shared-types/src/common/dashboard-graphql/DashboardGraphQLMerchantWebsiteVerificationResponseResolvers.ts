import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLMerchantWebsiteVerificationResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantWebsiteVerificationResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantWebsiteVerificationResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLMerchantWebsiteVerificationFailureResponse' | 'DashboardGraphQLMerchantWebsiteVerificationSuccessResponse',
    ParentType,
    ContextType
  >;
};