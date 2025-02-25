import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLMerchantFieldInterfaceResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantFieldInterface'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantFieldInterface'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    | 'DashboardGraphQLMerchantAverageOrderField'
    | 'DashboardGraphQLMerchantBusinessTypeField'
    | 'DashboardGraphQLMerchantDocumentField'
    | 'DashboardGraphQLMerchantEmailField'
    | 'DashboardGraphQLMerchantNumberField'
    | 'DashboardGraphQLMerchantPhoneField'
    | 'DashboardGraphQLMerchantStringField'
    | 'MerchantURLField',
    ParentType,
    ContextType
  >;
  clarificationReasons?: DashboardGraphQLResolver<
    Array<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantFieldClarificationReason']>,
    ParentType,
    ContextType
  >;
  verificationStatus?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantVerificationStatusEnum']>,
    ParentType,
    ContextType
  >;
};