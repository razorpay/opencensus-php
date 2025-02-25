import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLMaybe, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLPaymentResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLPayment'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLPayment'],
> = {
  amount?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLPaymentAmount'], ParentType, ContextType>;
  createdAt?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DateTime'], ParentType, ContextType>;
  customer?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLCustomer']>, ParentType, ContextType>;
  description?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['String']>, ParentType, ContextType>;
  error?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLPaymentError']>, ParentType, ContextType>;
  feeBearer?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLFeeBearerEnum']>, ParentType, ContextType>;
  id?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['ID'], ParentType, ContextType>;
  isCaptured?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['Boolean'], ParentType, ContextType>;
  isInternational?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['Boolean'], ParentType, ContextType>;
  method?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLPaymentMethod']>, ParentType, ContextType>;
  notes?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['JSONObject']>, ParentType, ContextType>;
  refunds?: DashboardGraphQLResolver<DashboardGraphQLMaybe<Array<DashboardGraphQLResolversTypes['DashboardGraphQLPaymentRefund']>>, ParentType, ContextType>;
  status?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLPaymentStatusEnum'], ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};