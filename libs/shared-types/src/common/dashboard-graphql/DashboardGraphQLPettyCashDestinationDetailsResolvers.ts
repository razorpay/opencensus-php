import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLMaybe, DashboardGraphQLResolversTypes, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLPettyCashDestinationDetailsResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLPettyCashDestinationDetails'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLPettyCashDestinationDetails'],
> = {
  name?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['String']>, ParentType, ContextType>;
  vpa?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['VPA']>, ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};