import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLOauthTokenAppleWatchResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLOauthTokenAppleWatchResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLOauthTokenAppleWatchResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLOauthTokenAppleWatchResponseError' | 'DashboardGraphQLOauthTokenAppleWatchResponseSuccess',
    ParentType,
    ContextType
  >;
};