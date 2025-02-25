import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLMaybe, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLPaymentLinkResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLPaymentLink'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLPaymentLink'],
> = {
  amount?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLPaymentLinkAmount'], ParentType, ContextType>;
  customer?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLCustomer']>, ParentType, ContextType>;
  dates?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLPaymentLinkDate'], ParentType, ContextType>;
  description?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['String']>, ParentType, ContextType>;
  id?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['ID'], ParentType, ContextType>;
  isPartiallyPayable?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['Boolean']>, ParentType, ContextType>;
  notes?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['JSONObject']>, ParentType, ContextType>;
  notifyBy?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLPaymentLinkNotifyBy']>, ParentType, ContextType>;
  orderId?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['String']>, ParentType, ContextType>;
  payments?: DashboardGraphQLResolver<DashboardGraphQLMaybe<Array<DashboardGraphQLResolversTypes['DashboardGraphQLPayment']>>, ParentType, ContextType>;
  referenceId?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['String']>, ParentType, ContextType>;
  reminder?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLPaymentLinkReminder']>, ParentType, ContextType>;
  status?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLPaymentLinkStatusEnum'], ParentType, ContextType>;
  url?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['URL'], ParentType, ContextType>;
  user?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLUser']>, ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};