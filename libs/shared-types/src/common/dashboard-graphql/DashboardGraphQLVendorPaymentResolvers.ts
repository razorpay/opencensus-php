import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLMaybe, DashboardGraphQLResolversTypes, DashboardGraphQLVendorPaymentPayoutsArgs, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLVendorPaymentResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLVendorPayment'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLVendorPayment'],
> = {
  cancelledBy?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLUser']>, ParentType, ContextType>;
  createdBy?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLUser'], ParentType, ContextType>;
  dates?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLVendorPaymentDates'], ParentType, ContextType>;
  description?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['String']>, ParentType, ContextType>;
  fundAccount?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantContactFundAccount']>,
    ParentType,
    ContextType
  >;
  gst?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['VendorPaymentGST']>, ParentType, ContextType>;
  id?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['ID'], ParentType, ContextType>;
  invoice?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLVendorPaymentInvoice'], ParentType, ContextType>;
  merchantContact?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantContact']>, ParentType, ContextType>;
  notes?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['JSONObject']>, ParentType, ContextType>;
  payoutAmounts?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLVendorPaymentPayoutAmounts']>,
    ParentType,
    ContextType
  >;
  payouts?: DashboardGraphQLResolver<
    Array<DashboardGraphQLResolversTypes['DashboardGraphQLPayout']>,
    ParentType,
    ContextType,
    Partial<DashboardGraphQLVendorPaymentPayoutsArgs>
  >;
  status?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLVendorPaymentStatusEnum'], ParentType, ContextType>;
  subtotal?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLMoney']>, ParentType, ContextType>;
  tds?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['VendorPaymentTDS']>, ParentType, ContextType>;
  total?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLMoney']>, ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};