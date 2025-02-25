import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLValidateVpaResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLValidateVpaResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLValidateVpaResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLValidateVpaFailureResponse' | 'DashboardGraphQLValidateVpaSuccessResponse',
    ParentType,
    ContextType
  >;
};