import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLMaybe, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLWithdrawalsListResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLWithdrawalsList'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLWithdrawalsList'],
> = {
  status?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLWithdrawalListStatus'], ParentType, ContextType>;
  total_outstanding_balance?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['String']>, ParentType, ContextType>;
  withdrawal_amount?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['Int'], ParentType, ContextType>;
  withdrawal_id?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['String'], ParentType, ContextType>;
  withdrawn_at?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};