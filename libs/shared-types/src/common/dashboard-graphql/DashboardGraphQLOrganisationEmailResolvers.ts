import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLOrganisationEmailResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLOrganisationEmail'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLOrganisationEmail'],
> = {
  from?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['EmailAddress'], ParentType, ContextType>;
  to?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['EmailAddress'], ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};