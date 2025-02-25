import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLMaybe, DashboardGraphQLResolversTypes, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLMerchantPoaStatusResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantPoaStatus'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantPoaStatus'],
> = {
  poaVerificationErrorCode?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantPoaVerificationErrorCodeEnum']>,
    ParentType,
    ContextType
  >;
  poaVerificationStatus?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantVerificationStatusEnum']>,
    ParentType,
    ContextType
  >;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};