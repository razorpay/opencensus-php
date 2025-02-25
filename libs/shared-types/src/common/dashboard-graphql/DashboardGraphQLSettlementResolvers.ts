import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLMaybe, DashboardGraphQLRequireFields, DashboardGraphQLSettlementTransactionSourcesArgs, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLSettlementResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLSettlement'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLSettlement'],
> = {
  amount?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLSettlementAmount'], ParentType, ContextType>;
  breakUp?: DashboardGraphQLResolver<DashboardGraphQLMaybe<Array<DashboardGraphQLResolversTypes['DashboardGraphQLSettlementBreakup']>>, ParentType, ContextType>;
  createdAt?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DateTime'], ParentType, ContextType>;
  id?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['ID'], ParentType, ContextType>;
  status?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLSettlementStatusEnum'], ParentType, ContextType>;
  transactionSources?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<Array<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLTransactionSourceDetails']>>>,
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLSettlementTransactionSourcesArgs, 'sourceType'>
  >;
  utr?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};