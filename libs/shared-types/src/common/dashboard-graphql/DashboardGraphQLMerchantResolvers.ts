import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLMaybe, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLMerchantResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchant'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchant'],
> = {
  activation?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantActivation'], ParentType, ContextType>;
  apiKeys?: DashboardGraphQLResolver<Array<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantApiKey']>, ParentType, ContextType>;
  bank?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantBank'], ParentType, ContextType>;
  bankingAccounts?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<Array<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantBankingAccount']>>,
    ParentType,
    ContextType
  >;
  business?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantBusiness'], ParentType, ContextType>;
  categoryModulePlacement?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLCategoryModulePlacementEnum']>,
    ParentType,
    ContextType
  >;
  configurations?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantConfiguration']>,
    ParentType,
    ContextType
  >;
  contactPerson?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantContactPerson'], ParentType, ContextType>;
  createdAt?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DateTime'], ParentType, ContextType>;
  disableSubCategoryRecommendations?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['Boolean'], ParentType, ContextType>;
  document?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantDocument'], ParentType, ContextType>;
  domesticLimit?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['BigInt']>, ParentType, ContextType>;
  eddEligibility?: DashboardGraphQLResolver<
    Array<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLEddEligibilityEnum']>>,
    ParentType,
    ContextType
  >;
  hasApiKeyAccess?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['Boolean'], ParentType, ContextType>;
  id?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['ID'], ParentType, ContextType>;
  internationalLimit?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['BigInt']>, ParentType, ContextType>;
  isFundsOnHold?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['Boolean'], ParentType, ContextType>;
  isPresignupComplete?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['Boolean']>, ParentType, ContextType>;
  isSubMerchant?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['Boolean']>, ParentType, ContextType>;
  isTransactionCouponApplied?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['Boolean']>, ParentType, ContextType>;
  isTwoFactorEnabled?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['Boolean'], ParentType, ContextType>;
  logo?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['URL']>, ParentType, ContextType>;
  merchantVerificationDetail?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<Array<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantVerificationDetail']>>>,
    ParentType,
    ContextType
  >;
  name?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantName']>, ParentType, ContextType>;
  poaStatus?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantPoaStatus'], ParentType, ContextType>;
  role?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantRoleEnum']>, ParentType, ContextType>;
  stakeholder?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantStakeholder'], ParentType, ContextType>;
  subCategoryRecommendations?: DashboardGraphQLResolver<
    Array<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantSubCategoryRecommendations']>>,
    ParentType,
    ContextType
  >;
  users?: DashboardGraphQLResolver<Array<DashboardGraphQLResolversTypes['DashboardGraphQLUser']>, ParentType, ContextType>;
  websiteTermsAndConditions?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantWebsiteTermsAndConditions']>,
    ParentType,
    ContextType
  >;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};