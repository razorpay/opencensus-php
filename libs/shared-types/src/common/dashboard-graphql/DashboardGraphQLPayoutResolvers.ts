import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLMaybe, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLPayoutResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLPayout'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLPayout'],
> = {
  amount?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMoney'], ParentType, ContextType>;
  bankingAccount?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantBankingAccount']>,
    ParentType,
    ContextType
  >;
  batchId?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['String']>, ParentType, ContextType>;
  cancelledBy?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLUser']>, ParentType, ContextType>;
  dates?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLPayoutDate']>, ParentType, ContextType>;
  failureReason?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['String']>, ParentType, ContextType>;
  fee?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLPayoutFee']>, ParentType, ContextType>;
  fundAccount?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantContactFundAccount']>,
    ParentType,
    ContextType
  >;
  id?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['ID'], ParentType, ContextType>;
  internalStatus?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLPayoutInternalStatusEnum']>,
    ParentType,
    ContextType
  >;
  isPendingOnMe?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['Boolean'], ParentType, ContextType>;
  mode?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLPayoutModeEnum'], ParentType, ContextType>;
  narration?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['String']>, ParentType, ContextType>;
  notes?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['JSONObject']>, ParentType, ContextType>;
  pendingReason?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['String']>, ParentType, ContextType>;
  purpose?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['String'], ParentType, ContextType>;
  referenceId?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['String']>, ParentType, ContextType>;
  remark?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['String']>, ParentType, ContextType>;
  sources?: DashboardGraphQLResolver<Array<DashboardGraphQLResolversTypes['DashboardGraphQLPayoutSource']>, ParentType, ContextType>;
  status?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLPayoutStatusEnum'], ParentType, ContextType>;
  statusDetails?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['JSONObject']>, ParentType, ContextType>;
  tax?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLMoney']>, ParentType, ContextType>;
  transaction?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLTransaction']>, ParentType, ContextType>;
  user?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLUser']>, ParentType, ContextType>;
  utr?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['String']>, ParentType, ContextType>;
  workflow?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLPayoutWorkflow']>, ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};