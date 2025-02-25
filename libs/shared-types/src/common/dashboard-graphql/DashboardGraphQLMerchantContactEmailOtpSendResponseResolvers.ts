import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLMerchantContactEmailOtpSendResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantContactEmailOtpSendResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantContactEmailOtpSendResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLMerchantContactEmailOtpSendFailureResponse' | 'DashboardGraphQLMerchantContactEmailOtpSendSuccessResponse',
    ParentType,
    ContextType
  >;
};