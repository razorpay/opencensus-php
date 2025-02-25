import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLMaybe, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLMerchantBusinessSubCategoryResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantBusinessSubCategory'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantBusinessSubCategory'],
> = {
  activationFlow?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantActivationFlowEnum'], ParentType, ContextType>;
  name?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['String'], ParentType, ContextType>;
  nonRegisteredActivationFlow?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantActivationFlowEnum'],
    ParentType,
    ContextType
  >;
  tags?: DashboardGraphQLResolver<Array<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['String']>>, ParentType, ContextType>;
  value?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};