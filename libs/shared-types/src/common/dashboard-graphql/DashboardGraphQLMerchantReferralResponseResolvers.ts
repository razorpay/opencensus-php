import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLMerchantReferralResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantReferralResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantReferralResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLMerchantReferralFailureResponse' | 'DashboardGraphQLMerchantReferralSuccessResponse',
    ParentType,
    ContextType
  >;
};