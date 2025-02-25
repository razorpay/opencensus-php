import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLOrderCreateResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLOrderCreateResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLOrderCreateResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLOrderCreateFailureResponse' | 'DashboardGraphQLOrderCreateSuccessResponse',
    ParentType,
    ContextType
  >;
};