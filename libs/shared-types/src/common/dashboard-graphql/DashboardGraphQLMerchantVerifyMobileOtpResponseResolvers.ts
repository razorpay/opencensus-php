import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLMerchantVerifyMobileOtpResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['MerchantVerifyMobileOTPResponse'] = DashboardGraphQLResolversParentTypes['MerchantVerifyMobileOTPResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'MerchantVerifyMobileOTPResponseFailure' | 'MerchantVerifyMobileOTPResponseSuccess',
    ParentType,
    ContextType
  >;
};