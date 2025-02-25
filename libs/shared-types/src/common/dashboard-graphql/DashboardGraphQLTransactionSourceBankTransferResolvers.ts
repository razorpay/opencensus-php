import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLTransactionSourceBankTransferResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLTransactionSourceBankTransfer'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLTransactionSourceBankTransfer'],
> = {
  id?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['ID'], ParentType, ContextType>;
  mode?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLTransactionSourceBankTransferModeEnum'], ParentType, ContextType>;
  payee?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLTransactionSourceBankTransferPayee'], ParentType, ContextType>;
  payer?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLTransactionSourceBankTransferPayer'], ParentType, ContextType>;
  reference?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};