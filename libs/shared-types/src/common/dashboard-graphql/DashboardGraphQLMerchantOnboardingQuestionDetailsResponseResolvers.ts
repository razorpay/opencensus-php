import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLMerchantOnboardingQuestionDetailsResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantOnboardingQuestionDetailsResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantOnboardingQuestionDetailsResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    | 'DashboardGraphQLMerchantOnboardingQuestionDetailsFailureResponse'
    | 'DashboardGraphQLMerchantOnboardingQuestionDetailsSuccessResponse',
    ParentType,
    ContextType
  >;
};