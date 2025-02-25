import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLMaybe, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLBudgetResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLBudget'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLBudget'],
> = {
  availableAmount?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMoney'], ParentType, ContextType>;
  balanceId?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['ID']>, ParentType, ContextType>;
  createdBy?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLUser'], ParentType, ContextType>;
  dates?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLBudgetDate'], ParentType, ContextType>;
  id?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['ID'], ParentType, ContextType>;
  isRecurring?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['Boolean']>, ParentType, ContextType>;
  name?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['String'], ParentType, ContextType>;
  status?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLBudgetStatusEnum'], ParentType, ContextType>;
  type?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLBudgetTypeEnum'], ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};