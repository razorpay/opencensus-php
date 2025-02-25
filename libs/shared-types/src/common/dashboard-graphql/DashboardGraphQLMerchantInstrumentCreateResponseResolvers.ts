import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLMerchantInstrumentCreateResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantInstrumentCreateResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantInstrumentCreateResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLMerchantInstrumentCreateFailureResponse' | 'DashboardGraphQLMerchantInstrumentCreateSuccessResponse',
    ParentType,
    ContextType
  >;
};