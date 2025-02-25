import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLMerchantKycPartnerAccessStatusUpdateResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['MerchantKYCPartnerAccessStatusUpdateResponse'] = DashboardGraphQLResolversParentTypes['MerchantKYCPartnerAccessStatusUpdateResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    | 'MerchantKYCPartnerAccessUpdateFailureResponse'
    | 'MerchantKYCPartnerAccessUpdateSuccessResponse',
    ParentType,
    ContextType
  >;
};