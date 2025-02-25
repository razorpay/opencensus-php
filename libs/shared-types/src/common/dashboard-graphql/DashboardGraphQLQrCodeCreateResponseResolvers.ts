import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn } from './index';
export type DashboardGraphQLQrCodeCreateResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['QRCodeCreateResponse'] = DashboardGraphQLResolversParentTypes['QRCodeCreateResponse'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    'QRCodeCreateFailureResponse' | 'QRCodeCreateSuccessResponse',
    ParentType,
    ContextType
  >;
};