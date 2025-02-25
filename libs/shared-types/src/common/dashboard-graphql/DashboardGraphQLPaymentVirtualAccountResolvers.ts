import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLMaybe, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLPaymentVirtualAccountResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLPaymentVirtualAccount'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLPaymentVirtualAccount'],
> = {
  amount?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLPaymentVirtualAccountAmount'], ParentType, ContextType>;
  customer?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLCustomer']>, ParentType, ContextType>;
  dates?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLPaymentVirtualAccountDates'], ParentType, ContextType>;
  description?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['String']>, ParentType, ContextType>;
  id?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['ID'], ParentType, ContextType>;
  name?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['String'], ParentType, ContextType>;
  notes?: DashboardGraphQLResolver<DashboardGraphQLMaybe<Array<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['JSONObject']>>>, ParentType, ContextType>;
  status?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};