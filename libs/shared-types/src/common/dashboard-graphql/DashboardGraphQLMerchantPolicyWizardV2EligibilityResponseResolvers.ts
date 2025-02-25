import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLMaybe, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLMerchantPolicyWizardV2EligibilityResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantPolicyWizardV2EligibilityResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantPolicyWizardV2EligibilityResponse'],
> = {
  code?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['PositiveInt'], ParentType, ContextType>;
  isPolicyWizardV2Enabled?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['Boolean'], ParentType, ContextType>;
  message?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['String']>, ParentType, ContextType>;
  policyEligibility?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantPolicyEligibilityEnum']>,
    ParentType,
    ContextType
  >;
  success?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['Boolean'], ParentType, ContextType>;
  websitePolicyVerificationStatus?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantVerificationStatusEnum']>,
    ParentType,
    ContextType
  >;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};