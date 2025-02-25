import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLMaybe, DashboardGraphQLResolversTypes, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLPpTrackingSettingsResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['PPTrackingSettings'] = DashboardGraphQLResolversParentTypes['PPTrackingSettings'],
> = {
  ppFbEventAddToCartEnabled?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['String']>, ParentType, ContextType>;
  ppFbEventInitiatePaymentEnabled?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['String']>,
    ParentType,
    ContextType
  >;
  ppFbEventPaymentCompleteEnabled?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['String']>,
    ParentType,
    ContextType
  >;
  ppFbPixelTrackingId?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['String']>, ParentType, ContextType>;
  ppGaPixelTrackingId?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};