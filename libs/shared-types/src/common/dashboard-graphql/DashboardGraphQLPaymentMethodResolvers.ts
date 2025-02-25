import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLPaymentMethodResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLPaymentMethod'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLPaymentMethod'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    | 'DashboardGraphQLPaymentMethodApp'
    | 'DashboardGraphQLPaymentMethodBankTransfer'
    | 'DashboardGraphQLPaymentMethodCard'
    | 'DashboardGraphQLPaymentMethodCardlessEmi'
    | 'DashboardGraphQLPaymentMethodEmandate'
    | 'DashboardGraphQLPaymentMethodEmi'
    | 'DashboardGraphQLPaymentMethodNetBanking'
    | 'DashboardGraphQLPaymentMethodPayLater'
    | 'PaymentMethodUPITransfer'
    | 'DashboardGraphQLPaymentMethodWallet',
    ParentType,
    ContextType
  >;
};