import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLMerchantSendMobileOtpResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['MerchantSendMobileOTPResponse'] = DashboardGraphQLResolversParentTypes['MerchantSendMobileOTPResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'MerchantSendMobileOTPResponseFailure' | 'MerchantSendMobileOTPResponseSuccess',
    ParentType,
    ContextType
  >;
};