import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLMaybe, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLMerchantStringFieldResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantStringField'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantStringField'],
> = {
  clarificationReasons?: DashboardGraphQLResolver<
    Array<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantFieldClarificationReason']>,
    ParentType,
    ContextType
  >;
  suggestedValue?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['String']>, ParentType, ContextType>;
  value?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['String']>, ParentType, ContextType>;
  verificationStatus?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantVerificationStatusEnum']>,
    ParentType,
    ContextType
  >;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};