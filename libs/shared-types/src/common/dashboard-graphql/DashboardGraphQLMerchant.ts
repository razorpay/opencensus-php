import { DashboardGraphQLMerchantActivation, DashboardGraphQLMerchantApiKey, DashboardGraphQLMerchantBank, DashboardGraphQLMaybe, DashboardGraphQLMerchantBankingAccount, DashboardGraphQLMerchantBusiness, DashboardGraphQLCategoryModulePlacementEnum, DashboardGraphQLMerchantConfiguration, DashboardGraphQLMerchantContactPerson, DashboardGraphQLScalars, DashboardGraphQLMerchantDocument, DashboardGraphQLEddEligibilityEnum, DashboardGraphQLMerchantVerificationDetail, DashboardGraphQLMerchantName, DashboardGraphQLMerchantPoaStatus, DashboardGraphQLMerchantRoleEnum, DashboardGraphQLMerchantStakeholder, DashboardGraphQLMerchantSubCategoryRecommendations, DashboardGraphQLUser, DashboardGraphQLMerchantWebsiteTermsAndConditions } from './index';
export type DashboardGraphQLMerchant = {
  __typename?: 'DashboardGraphQLMerchant';
  activation: DashboardGraphQLMerchantActivation;
  apiKeys: Array<DashboardGraphQLMerchantApiKey>;
  bank: DashboardGraphQLMerchantBank;
  bankingAccounts?: DashboardGraphQLMaybe<Array<DashboardGraphQLMerchantBankingAccount>>;
  business: DashboardGraphQLMerchantBusiness;
  categoryModulePlacement?: DashboardGraphQLMaybe<DashboardGraphQLCategoryModulePlacementEnum>;
  configurations?: DashboardGraphQLMaybe<DashboardGraphQLMerchantConfiguration>;
  contactPerson: DashboardGraphQLMerchantContactPerson;
  createdAt: DashboardGraphQLScalars['DateTime'];
  disableSubCategoryRecommendations: DashboardGraphQLScalars['Boolean'];
  document: DashboardGraphQLMerchantDocument;
  domesticLimit?: DashboardGraphQLMaybe<DashboardGraphQLScalars['BigInt']>;
  eddEligibility: Array<DashboardGraphQLMaybe<DashboardGraphQLEddEligibilityEnum>>;
  hasApiKeyAccess: DashboardGraphQLScalars['Boolean'];
  id: DashboardGraphQLScalars['ID'];
  internationalLimit?: DashboardGraphQLMaybe<DashboardGraphQLScalars['BigInt']>;
  isFundsOnHold: DashboardGraphQLScalars['Boolean'];
  isPresignupComplete?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Boolean']>;
  isSubMerchant?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Boolean']>;
  /** @deprecated property removed from backend */
  isTransactionCouponApplied?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Boolean']>;
  isTwoFactorEnabled: DashboardGraphQLScalars['Boolean'];
  logo?: DashboardGraphQLMaybe<DashboardGraphQLScalars['URL']>;
  merchantVerificationDetail?: DashboardGraphQLMaybe<Array<DashboardGraphQLMaybe<DashboardGraphQLMerchantVerificationDetail>>>;
  name?: DashboardGraphQLMaybe<DashboardGraphQLMerchantName>;
  poaStatus: DashboardGraphQLMerchantPoaStatus;
  /** @deprecated Use user.roles */
  role?: DashboardGraphQLMaybe<DashboardGraphQLMerchantRoleEnum>;
  stakeholder: DashboardGraphQLMerchantStakeholder;
  subCategoryRecommendations: Array<DashboardGraphQLMaybe<DashboardGraphQLMerchantSubCategoryRecommendations>>;
  users: Array<DashboardGraphQLUser>;
  websiteTermsAndConditions?: DashboardGraphQLMaybe<DashboardGraphQLMerchantWebsiteTermsAndConditions>;
};