import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLMaybe, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLMerchantDocumentFieldResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantDocumentField'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantDocumentField'],
> = {
  clarificationReasons?: DashboardGraphQLResolver<
    Array<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantFieldClarificationReason']>,
    ParentType,
    ContextType
  >;
  values?: DashboardGraphQLResolver<Array<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantDocumentFieldValue']>, ParentType, ContextType>;
  verificationStatus?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantVerificationStatusEnum']>,
    ParentType,
    ContextType
  >;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};