import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLMaybe, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLQrCodeResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['QRCode'] = DashboardGraphQLResolversParentTypes['QRCode'],
> = {
  dates?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['QRCodeDate'], ParentType, ContextType>;
  description?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['String']>, ParentType, ContextType>;
  id?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['ID'], ParentType, ContextType>;
  imageURL?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['String'], ParentType, ContextType>;
  isFixedAmount?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['Boolean']>, ParentType, ContextType>;
  name?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['String']>, ParentType, ContextType>;
  paymentDetails?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['QRCodePaymentDetail'], ParentType, ContextType>;
  status?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['QRCodeStatusEnum'], ParentType, ContextType>;
  type?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['QRCodeTypeEnum'], ParentType, ContextType>;
  usage?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['QRCodeUsageEnum'], ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};