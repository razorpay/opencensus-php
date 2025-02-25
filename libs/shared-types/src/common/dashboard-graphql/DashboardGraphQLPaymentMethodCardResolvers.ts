import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLMaybe, DashboardGraphQLResolversTypes, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLPaymentMethodCardResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLPaymentMethodCard'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLPaymentMethodCard'],
> = {
  category?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLPaymentMethodCardCategoryEnum']>,
    ParentType,
    ContextType
  >;
  expiry?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLPaymentMethodCardExpiry'], ParentType, ContextType>;
  id?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['ID'], ParentType, ContextType>;
  isEmi?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['Boolean']>, ParentType, ContextType>;
  isInternational?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['Boolean']>, ParentType, ContextType>;
  issuer?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['String']>, ParentType, ContextType>;
  lastFourDigits?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['Int'], ParentType, ContextType>;
  name?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['String']>, ParentType, ContextType>;
  network?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['String'], ParentType, ContextType>;
  type?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLPaymentMethodCardType'], ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};