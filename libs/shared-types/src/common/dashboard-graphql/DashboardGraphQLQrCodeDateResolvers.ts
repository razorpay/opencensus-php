import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLMaybe, DashboardGraphQLResolversTypes, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLQrCodeDateResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['QRCodeDate'] = DashboardGraphQLResolversParentTypes['QRCodeDate'],
> = {
  createdAt?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DateTime']>, ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};