import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLAddressByPincodeResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLAddressByPincodeResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLAddressByPincodeResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLAddressByPincodeFailureResponse' | 'DashboardGraphQLAddressByPincodeSuccessResponse',
    ParentType,
    ContextType
  >;
};