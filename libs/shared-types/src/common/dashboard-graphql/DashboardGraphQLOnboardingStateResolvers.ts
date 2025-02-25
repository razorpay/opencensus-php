import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLMaybe, DashboardGraphQLResolversTypes, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLOnboardingStateResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLOnboardingState'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLOnboardingState'],
> = {
  milestones?: DashboardGraphQLResolver<Array<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['String']>>, ParentType, ContextType>;
  modularComponents?: DashboardGraphQLResolver<Array<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['String']>>, ParentType, ContextType>;
  steps?: DashboardGraphQLResolver<Array<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['String']>>, ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};