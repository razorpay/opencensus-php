import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLMerchantContactFundAccountDetailsResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantContactFundAccountDetails'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantContactFundAccountDetails'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    | 'DashboardGraphQLMerchantContactFundAccountDetailsBankAccount'
    | 'DashboardGraphQLMerchantContactFundAccountDetailsCard'
    | 'MerchantContactFundAccountDetailsVPA'
    | 'DashboardGraphQLMerchantContactFundAccountDetailsWallet',
    ParentType,
    ContextType
  >;
};