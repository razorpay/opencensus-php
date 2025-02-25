import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLMaybe, DashboardGraphQLResolversTypes, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLModularComponentMetaUiFieldResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLModularComponentMetaUiField'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLModularComponentMetaUiField'],
> = {
  meta?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLModularComponentMetaUiFieldMeta']>,
    ParentType,
    ContextType
  >;
  name?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['String'], ParentType, ContextType>;
  value?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};