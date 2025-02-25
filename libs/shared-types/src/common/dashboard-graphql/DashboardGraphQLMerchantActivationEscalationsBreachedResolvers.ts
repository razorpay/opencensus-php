import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLMaybe, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLMerchantActivationEscalationsBreachedResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantActivationEscalationsBreached'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantActivationEscalationsBreached'],
> = {
  amount?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMoney'], ParentType, ContextType>;
  currentEscalationLimit?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantEscalationLimit'],
    ParentType,
    ContextType
  >;
  escalationAction?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantEscalationAction']>,
    ParentType,
    ContextType
  >;
  escalationType?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantEscalationTypeEnum'], ParentType, ContextType>;
  id?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['ID'], ParentType, ContextType>;
  nextEscalationLimit?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantEscalationLimit']>,
    ParentType,
    ContextType
  >;
  transactionLimit?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantTransactionLimit'], ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};