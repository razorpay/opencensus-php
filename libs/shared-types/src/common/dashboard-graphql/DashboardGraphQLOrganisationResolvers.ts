import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLMaybe, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLOrganisationResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLOrganisation'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLOrganisation'],
> = {
  allowedEmailDomains?: DashboardGraphQLResolver<Array<DashboardGraphQLResolversTypes['String']>, ParentType, ContextType>;
  code?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['String'], ParentType, ContextType>;
  domain?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['String'], ParentType, ContextType>;
  email?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLOrganisationEmail'], ParentType, ContextType>;
  id?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['ID'], ParentType, ContextType>;
  logo?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLOrganisationLogo']>, ParentType, ContextType>;
  name?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLOrganisationName'], ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};