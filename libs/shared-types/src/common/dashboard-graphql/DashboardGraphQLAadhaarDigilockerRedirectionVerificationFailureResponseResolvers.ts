import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLMaybe, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLAadhaarDigilockerRedirectionVerificationFailureResponseResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLAadhaarDigilockerRedirectionVerificationFailureResponse'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLAadhaarDigilockerRedirectionVerificationFailureResponse'],
> = {
  code?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['PositiveInt'], ParentType, ContextType>;
  errorCode?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLAadhaarDigilockerRedirectionVerificationErrorTypeEnum'],
    ParentType,
    ContextType
  >;
  message?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['String']>, ParentType, ContextType>;
  success?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};