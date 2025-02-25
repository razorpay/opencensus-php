import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLTdsCategoryResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['TDSCategory'] = DashboardGraphQLResolversParentTypes['TDSCategory'],
> = {
  code?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['String'], ParentType, ContextType>;
  id?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['Int'], ParentType, ContextType>;
  name?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['String'], ParentType, ContextType>;
  rate?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['Float'], ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};