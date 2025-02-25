import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLMerchantOnboardingQuestionDetailResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantOnboardingQuestionDetail'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantOnboardingQuestionDetail'],
> = {
  answer?: DashboardGraphQLResolver<Array<DashboardGraphQLResolversTypes['String']>, ParentType, ContextType>;
  questionId?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['ID'], ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};