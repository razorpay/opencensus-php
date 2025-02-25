import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLMerchantSettlementConfigResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantSettlementConfigResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantSettlementConfigResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLMerchantSettlementConfigFailureResponse' | 'DashboardGraphQLMerchantSettlementConfigSuccessResponse',
    ParentType,
    ContextType
  >;
};