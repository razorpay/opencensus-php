import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLMaybe, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLPayoutRejectBulkResponseFailureResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLPayoutRejectBulkResponseFailure'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLPayoutRejectBulkResponseFailure'],
> = {
  code?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['PositiveInt'], ParentType, ContextType>;
  failedPayoutIds?: DashboardGraphQLResolver<DashboardGraphQLMaybe<Array<DashboardGraphQLResolversTypes['ID']>>, ParentType, ContextType>;
  message?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['String']>, ParentType, ContextType>;
  success?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};