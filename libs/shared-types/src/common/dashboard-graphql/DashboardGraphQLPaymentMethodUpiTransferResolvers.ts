import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLMaybe, DashboardGraphQLResolversTypes, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLPaymentMethodUpiTransferResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['PaymentMethodUPITransfer'] = DashboardGraphQLResolversParentTypes['PaymentMethodUPITransfer'],
> = {
  vpa?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};