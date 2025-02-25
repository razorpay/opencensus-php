import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLMerchantGstResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantGstResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantGstResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLMerchantGstFailureResponse' | 'DashboardGraphQLMerchantGstSuccessResponse',
    ParentType,
    ContextType
  >;
};