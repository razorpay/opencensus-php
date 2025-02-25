import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLMaybe, DashboardGraphQLResolversTypes, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLUserRoleResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLUserRole'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLUserRole'],
> = {
  banking?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLUserRoleBankingEnum']>, ParentType, ContextType>;
  bankingPermissions?: DashboardGraphQLResolver<Array<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['String']>>, ParentType, ContextType>;
  bankingRole?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantBankingRole']>, ParentType, ContextType>;
  merchant?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMerchant'], ParentType, ContextType>;
  payments?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLUserRolePaymentsEnum']>, ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};