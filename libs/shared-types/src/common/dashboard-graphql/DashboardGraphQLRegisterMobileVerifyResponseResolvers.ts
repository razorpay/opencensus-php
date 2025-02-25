import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLRegisterMobileVerifyResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLRegisterMobileVerifyResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLRegisterMobileVerifyResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLRegisterMobileVerifyResponseFailure' | 'DashboardGraphQLRegisterMobileVerifyResponseSuccess',
    ParentType,
    ContextType
  >;
};