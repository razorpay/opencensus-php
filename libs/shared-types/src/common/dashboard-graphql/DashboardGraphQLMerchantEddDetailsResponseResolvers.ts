import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLMerchantEddDetailsResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantEddDetailsResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantEddDetailsResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLMerchantEddDetailsFailureResponse' | 'DashboardGraphQLMerchantEddDetailsSuccessResponse',
    ParentType,
    ContextType
  >;
};