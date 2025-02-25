import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLMaybe, DashboardGraphQLResolversTypes, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLWithdrawalValidityResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLWithdrawalValidity'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLWithdrawalValidity'],
> = {
  emi_tenure_map?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<Array<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLEmiTenureMap']>>>,
    ParentType,
    ContextType
  >;
  min_valid_tenure?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['String']>, ParentType, ContextType>;
  withdrawal_possible?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['Boolean']>, ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};