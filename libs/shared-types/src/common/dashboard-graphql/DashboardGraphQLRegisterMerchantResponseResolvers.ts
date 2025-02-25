import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLRegisterMerchantResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLRegisterMerchantResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLRegisterMerchantResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLRegisterMerchantResponseFailure' | 'DashboardGraphQLRegisterMerchantResponseSuccess',
    ParentType,
    ContextType
  >;
};