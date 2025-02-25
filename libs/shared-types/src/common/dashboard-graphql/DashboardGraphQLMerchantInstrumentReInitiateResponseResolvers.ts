import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLMerchantInstrumentReInitiateResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantInstrumentReInitiateResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantInstrumentReInitiateResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'DashboardGraphQLMerchantInstrumentReInitiateFailureResponse' | 'DashboardGraphQLMerchantInstrumentReInitiateSuccessResponse',
    ParentType,
    ContextType
  >;
};