import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLAadhaarCaptchaV2ResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLAadhaarCaptchaV2Response'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLAadhaarCaptchaV2Response'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLAadhaarCaptchaV2FailureResponse' | 'DashboardGraphQLAadhaarCaptchaV2SuccessResponse',
    ParentType,
    ContextType
  >;
};