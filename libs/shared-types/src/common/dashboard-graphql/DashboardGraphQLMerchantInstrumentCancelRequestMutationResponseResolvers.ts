import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLMerchantInstrumentCancelRequestMutationResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantInstrumentCancelRequestMutationResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantInstrumentCancelRequestMutationResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    | 'DashboardGraphQLMerchantInstrumentCancelRequestMutationFailureResponse'
    | 'DashboardGraphQLMerchantInstrumentCancelRequestMutationSucessResponse',
    ParentType,
    ContextType
  >;
};