import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLTransactionSourceResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLTransactionSource'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLTransactionSource'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    | 'DashboardGraphQLPayout'
    | 'DashboardGraphQLTransactionSourceAdjustment'
    | 'DashboardGraphQLTransactionSourceBankTransfer'
    | 'DashboardGraphQLTransactionSourceExternal'
    | 'DashboardGraphQLTransactionSourceFundAccountValidation'
    | 'DashboardGraphQLTransactionSourceReversal',
    ParentType,
    ContextType
  >;
};