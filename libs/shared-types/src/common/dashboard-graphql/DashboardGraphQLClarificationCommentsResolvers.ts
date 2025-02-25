import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLMaybe, DashboardGraphQLResolversTypes, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLClarificationCommentsResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLClarificationComments'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLClarificationComments'],
> = {
  comment?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLClarificationComment']>, ParentType, ContextType>;
  createdAt?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['PositiveInt'], ParentType, ContextType>;
  fieldDetails?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantClarificationFieldValues']>,
    ParentType,
    ContextType
  >;
  messageFrom?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantClarificationFromEnum'], ParentType, ContextType>;
  ncCount?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['Int'], ParentType, ContextType>;
  status?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantClarificationStatusEnum'], ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};