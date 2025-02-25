import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLRequireFields, DashboardGraphQLQueryAddressByPincodeArgs, DashboardGraphQLQueryBankDetailsArgs, DashboardGraphQLQueryBudgetsArgs, DashboardGraphQLMaybe, DashboardGraphQLQueryEmiSummaryArgs, DashboardGraphQLQueryExpenseCategoriesArgs, DashboardGraphQLQueryFailedPaymentsOverviewArgs, DashboardGraphQLQueryInvoiceByIdArgs, DashboardGraphQLQueryInvoicesArgs, DashboardGraphQLQueryMerchantBalanceByIdArgs, DashboardGraphQLQueryMerchantBankingAccountsBalanceArgs, DashboardGraphQLQueryMerchantBusinessCategoriesArgs, DashboardGraphQLQueryMerchantByIdArgs, DashboardGraphQLQueryMerchantConfigArgs, DashboardGraphQLQueryMerchantConsentArgs, DashboardGraphQLQueryMerchantContactByIdArgs, DashboardGraphQLQueryMerchantContactFundAccountsArgs, DashboardGraphQLQueryMerchantContactsArgs, DashboardGraphQLQueryMerchantDocumentByIdArgs, DashboardGraphQLQueryMerchantFeatureFlagsArgs, DashboardGraphQLQueryMerchantIdentityArgs, DashboardGraphQLQueryMerchantKycPartnerAccessArgs, DashboardGraphQLQueryMerchantPaymentHandleAvailabilityArgs, DashboardGraphQLQueryMerchantPaymentHandleSuggestionsArgs, DashboardGraphQLQueryMerchantPolicyArgs, DashboardGraphQLQueryMerchantPolicyPreviewArgs, DashboardGraphQLQueryMerchantPolicyWizardV2EligibilityArgs, DashboardGraphQLQueryMerchantPreferencesArgs, DashboardGraphQLQueryMerchantSelfServeWorkflowStatusArgs, DashboardGraphQLQueryMerchantStoreByIdArgs, DashboardGraphQLQueryMerchantStoreListArgs, DashboardGraphQLQueryMerchantValidateSocialMediaUrlArgs, DashboardGraphQLQueryOnboardingPaymentDetailsArgs, DashboardGraphQLQueryOrganisationInformationArgs, DashboardGraphQLQueryOrganisationInformationByDomainArgs, DashboardGraphQLQueryPartnerConfigByIdArgs, DashboardGraphQLQueryPaymentAnalyticsArgs, DashboardGraphQLQueryPaymentByIdArgs, DashboardGraphQLQueryPaymentInstantRefundEligibilityArgs, DashboardGraphQLQueryPaymentLinkByIdArgs, DashboardGraphQLQueryPaymentLinksArgs, DashboardGraphQLQueryPaymentOverviewArgs, DashboardGraphQLQueryPaymentPageByIdArgs, DashboardGraphQLQueryPaymentPageTransactionsByIdArgs, DashboardGraphQLQueryPaymentPagesArgs, DashboardGraphQLQueryPaymentsArgs, DashboardGraphQLQueryPayoutBatchByIdArgs, DashboardGraphQLQueryPayoutBatchesArgs, DashboardGraphQLQueryPayoutByIdArgs, DashboardGraphQLQueryPayoutLinkByIdArgs, DashboardGraphQLQueryPayoutLinksArgs, DashboardGraphQLQueryPayoutsArgs, DashboardGraphQLQueryPayoutsWorkflowConfigArgs, DashboardGraphQLQueryPettyCashByIdArgs, DashboardGraphQLQueryQrCodesArgs, DashboardGraphQLQueryRefundByIdArgs, DashboardGraphQLQueryRefundsArgs, DashboardGraphQLQuerySalesOnboardedMerchantsArgs, DashboardGraphQLQuerySettlementByIdArgs, DashboardGraphQLQuerySettlementByUtrArgs, DashboardGraphQLQuerySettlementsArgs, DashboardGraphQLQueryTransactionByIdArgs, DashboardGraphQLQueryTransactionsArgs, DashboardGraphQLQueryUserByIdArgs, DashboardGraphQLQueryValidateVpaArgs, DashboardGraphQLQueryVendorPaymentByIdArgs, DashboardGraphQLQueryVendorPaymentsArgs, DashboardGraphQLQueryWhatsappNotificationStatusArgs, DashboardGraphQLQueryWithdrawalValidityArgs } from './index';
export type DashboardGraphQLQueryResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLQuery'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLQuery'],
> = {
  aadhaarCaptcha?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLAadhaarCaptchaResponse'], ParentType, ContextType>;
  aadhaarCaptchaV2?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLAadhaarCaptchaV2Response'], ParentType, ContextType>;
  addressByPincode?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLAddressByPincodeResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQueryAddressByPincodeArgs, 'pincode'>
  >;
  bankDetails?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLBank'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQueryBankDetailsArgs, 'ifsc'>
  >;
  budgets?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLBudgetsResponse'],
    ParentType,
    ContextType,
    Partial<DashboardGraphQLQueryBudgetsArgs>
  >;
  emiSummary?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['EMISummary']>,
    ParentType,
    ContextType,
    Partial<DashboardGraphQLQueryEmiSummaryArgs>
  >;
  expenseCategories?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLExpenseCategoriesResponse'],
    ParentType,
    ContextType,
    Partial<DashboardGraphQLQueryExpenseCategoriesArgs>
  >;
  failedPaymentsOverview?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLFailedPaymentsOverviewResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQueryFailedPaymentsOverviewArgs, 'entity' | 'fromDate' | 'limit' | 'toDate'>
  >;
  invoiceById?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLInvoice'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQueryInvoiceByIdArgs, 'id'>
  >;
  invoices?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLInvoicesResponse'],
    ParentType,
    ContextType,
    Partial<DashboardGraphQLQueryInvoicesArgs>
  >;
  merchantAnalytics?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantAnalytics'], ParentType, ContextType>;
  merchantBalanceById?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantBalance'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQueryMerchantBalanceByIdArgs, 'id'>
  >;
  merchantBankDetails?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantBankDetailsResponse'],
    ParentType,
    ContextType
  >;
  merchantBankingAccountsBalance?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantBankingAccountsBalanceResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQueryMerchantBankingAccountsBalanceArgs, 'type'>
  >;
  merchantBankingRoles?: DashboardGraphQLResolver<
    Array<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantBankingRole']>>,
    ParentType,
    ContextType
  >;
  merchantBusinessCategories?: DashboardGraphQLResolver<
    Array<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantBusinessCategoriesResponse']>,
    ParentType,
    ContextType,
    Partial<DashboardGraphQLQueryMerchantBusinessCategoriesArgs>
  >;
  merchantBusinessCategoriesV2?: DashboardGraphQLResolver<
    Array<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantBusinessCategoriesResponseV2']>,
    ParentType,
    ContextType
  >;
  merchantBusinessParentCategories?: DashboardGraphQLResolver<
    Array<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantBusinessParentCategory']>,
    ParentType,
    ContextType
  >;
  merchantBusinessTypes?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantBusinessTypesResponse'],
    ParentType,
    ContextType
  >;
  merchantById?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchant'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQueryMerchantByIdArgs, 'id'>
  >;
  merchantClarificationDetails?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantClarificationDetailsResponse']>,
    ParentType,
    ContextType
  >;
  merchantConfig?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantConfig'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQueryMerchantConfigArgs, 'namespace'>
  >;
  merchantConsent?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantConsentResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQueryMerchantConsentArgs, 'partnerId'>
  >;
  merchantContactById?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantContact']>,
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQueryMerchantContactByIdArgs, 'id'>
  >;
  merchantContactFundAccounts?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantContactFundAccountsResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQueryMerchantContactFundAccountsArgs, 'contactId' | 'limit' | 'offset'>
  >;
  merchantContactTypes?: DashboardGraphQLResolver<Array<DashboardGraphQLResolversTypes['String']>, ParentType, ContextType>;
  merchantContacts?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantContactsResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQueryMerchantContactsArgs, 'active'>
  >;
  merchantCreditBalance?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantCreditBalanceResponse'],
    ParentType,
    ContextType
  >;
  merchantDocumentById?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantDocumentByIdResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQueryMerchantDocumentByIdArgs, 'documentId'>
  >;
  merchantEddDetails?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantEddDetailsResponse'],
    ParentType,
    ContextType
  >;
  merchantFeatureFlags?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<Array<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantFeatureFlag']>>,
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQueryMerchantFeatureFlagsArgs, 'names'>
  >;
  merchantGst?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantGstResponse'], ParentType, ContextType>;
  merchantGstins?: DashboardGraphQLResolver<Array<DashboardGraphQLResolversTypes['String']>, ParentType, ContextType>;
  merchantIdentity?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<Array<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantIdentityResponse']>>,
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQueryMerchantIdentityArgs, 'searchQuery'>
  >;
  merchantInstrumentsStatus?: DashboardGraphQLResolver<
    Array<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantInstrumentsStatus']>>,
    ParentType,
    ContextType
  >;
  merchantKYCPartnerAccess?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['MerchantKYCPartnerAccessResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQueryMerchantKycPartnerAccessArgs, 'referralCode'>
  >;
  merchantModularOnboardingDetails?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['merchantModularOnboardingDetailsResponse']>,
    ParentType,
    ContextType
  >;
  merchantNeedsClarificationEligibility?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantNcEligibilityResponse'],
    ParentType,
    ContextType
  >;
  merchantOnboardingQuestionDetails?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantOnboardingQuestionDetailsResponse'],
    ParentType,
    ContextType
  >;
  merchantPaymentHandle?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantPaymentHandleResponse'],
    ParentType,
    ContextType
  >;
  merchantPaymentHandleAvailability?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantPaymentHandleAvailabilityResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQueryMerchantPaymentHandleAvailabilityArgs, 'paymentHandleSlug'>
  >;
  merchantPaymentHandleSuggestions?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantPaymentHandleSuggestionsResponse'],
    ParentType,
    ContextType,
    Partial<DashboardGraphQLQueryMerchantPaymentHandleSuggestionsArgs>
  >;
  merchantPolicy?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantPolicyResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQueryMerchantPolicyArgs, 'publishedUrl' | 'section'>
  >;
  merchantPolicyPreview?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantPolicyPreviewResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQueryMerchantPolicyPreviewArgs, 'section'>
  >;
  merchantPolicyPreviewV2?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantPolicyPreviewV2Response'],
    ParentType,
    ContextType
  >;
  merchantPolicyWizardV2Eligibility?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantPolicyWizardV2EligibilityResponse'],
    ParentType,
    ContextType,
    Partial<DashboardGraphQLQueryMerchantPolicyWizardV2EligibilityArgs>
  >;
  merchantPreferences?: DashboardGraphQLResolver<
    Array<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantPreference']>>,
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQueryMerchantPreferencesArgs, 'preferenceGroup'>
  >;
  merchantReferral?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantReferralResponse'], ParentType, ContextType>;
  merchantSelfServeWorkflowStatus?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantSelfServeWorkflowStatusResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQueryMerchantSelfServeWorkflowStatusArgs, 'workflow'>
  >;
  merchantSettlementConfig?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantSettlementConfigResponse'],
    ParentType,
    ContextType
  >;
  merchantStoreById?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantStore'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQueryMerchantStoreByIdArgs, 'id'>
  >;
  merchantStoreList?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantStoreListResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQueryMerchantStoreListArgs, 'limit' | 'offset'>
  >;
  merchantSupportDetails?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantSupportDetailsResponse'],
    ParentType,
    ContextType
  >;
  merchantValidateSocialMediaUrl?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['MerchantValidateSocialMediaURLResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQueryMerchantValidateSocialMediaUrlArgs, 'platform' | 'url'>
  >;
  merchantWebsites?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantWebsitesResponse'], ParentType, ContextType>;
  onboardingPaymentDetails?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLMerchantOnboardingPaymentDetails'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQueryOnboardingPaymentDetailsArgs, 'paymentType'>
  >;
  organisationInformation?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLOrganisation'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQueryOrganisationInformationArgs, 'domainName'>
  >;
  organisationInformationByDomain?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLOrganisation'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQueryOrganisationInformationByDomainArgs, 'domain'>
  >;
  partnerConfigById?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLPartnerConfigResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQueryPartnerConfigByIdArgs, 'id'>
  >;
  paymentAnalytics?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLPaymentAnalyticsResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<
      DashboardGraphQLQueryPaymentAnalyticsArgs,
      'aggregateBy' | 'columnName' | 'fromDate' | 'indexName' | 'toDate'
    >
  >;
  paymentById?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLPayment'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQueryPaymentByIdArgs, 'id'>
  >;
  paymentInstantRefundEligibility?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLPaymentInstantRefundEligibilityResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQueryPaymentInstantRefundEligibilityArgs, 'amount' | 'id'>
  >;
  paymentLinkById?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLPaymentLink'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQueryPaymentLinkByIdArgs, 'id'>
  >;
  paymentLinks?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLPaymentLinksResponse'],
    ParentType,
    ContextType,
    Partial<DashboardGraphQLQueryPaymentLinksArgs>
  >;
  paymentOverview?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLPaymentOverviewResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQueryPaymentOverviewArgs, 'fromDate' | 'toDate'>
  >;
  paymentPageById?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLPaymentPage'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQueryPaymentPageByIdArgs, 'id'>
  >;
  paymentPageTransactionsById?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLPaymentPageTransactionResponse']>,
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQueryPaymentPageTransactionsByIdArgs, 'paymentPageId'>
  >;
  paymentPages?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLPaymentPagesResponse']>,
    ParentType,
    ContextType,
    Partial<DashboardGraphQLQueryPaymentPagesArgs>
  >;
  payments?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLPaymentsResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQueryPaymentsArgs, 'limit' | 'offset'>
  >;
  paymentsWidgets?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLPaymentsWidgets'], ParentType, ContextType>;
  payoutBatchById?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLPayoutBatch'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQueryPayoutBatchByIdArgs, 'payoutBatchId'>
  >;
  payoutBatches?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLPayoutBatchesResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQueryPayoutBatchesArgs, 'limit' | 'offset'>
  >;
  payoutById?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLPayout'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQueryPayoutByIdArgs, 'id'>
  >;
  payoutLinkById?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLPayoutLink'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQueryPayoutLinkByIdArgs, 'id'>
  >;
  payoutLinks?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLPayoutLinksResponse'],
    ParentType,
    ContextType,
    Partial<DashboardGraphQLQueryPayoutLinksArgs>
  >;
  payoutPurposes?: DashboardGraphQLResolver<Array<DashboardGraphQLResolversTypes['DashboardGraphQLPayoutPurpose']>, ParentType, ContextType>;
  payouts?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLPayoutsResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQueryPayoutsArgs, 'limit' | 'offset'>
  >;
  payoutsSummary?: DashboardGraphQLResolver<Array<DashboardGraphQLResolversTypes['DashboardGraphQLPayoutsSummary']>, ParentType, ContextType>;
  payoutsWorkflowConfig?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLWorkflowConfig']>,
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQueryPayoutsWorkflowConfigArgs, 'configType'>
  >;
  pettyCashById?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLPettyCash'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQueryPettyCashByIdArgs, 'id'>
  >;
  pointOfSaleKeysFetch?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLPointOfSaleKeyFetchResponse'],
    ParentType,
    ContextType
  >;
  qrCodes?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['QRCodesResponse'],
    ParentType,
    ContextType,
    Partial<DashboardGraphQLQueryQrCodesArgs>
  >;
  refundById?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLPaymentRefund'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQueryRefundByIdArgs, 'id'>
  >;
  refunds?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLRefundsResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQueryRefundsArgs, 'limit' | 'offset'>
  >;
  repaymentsSummary?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLRepaymentsSummary']>, ParentType, ContextType>;
  salesOnboardedMerchants?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLSalesOnboardedMerchantsResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<
      DashboardGraphQLQuerySalesOnboardedMerchantsArgs,
      'endDate' | 'limit' | 'offset' | 'startDate' | 'status'
    >
  >;
  settlementById?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLSettlement'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQuerySettlementByIdArgs, 'id'>
  >;
  settlementByUtr?: DashboardGraphQLResolver<
    Array<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLSettlement']>>,
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQuerySettlementByUtrArgs, 'utr'>
  >;
  settlementCycle?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLSettlementCycle'], ParentType, ContextType>;
  settlements?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLSettlementsResponse'],
    ParentType,
    ContextType,
    Partial<DashboardGraphQLQuerySettlementsArgs>
  >;
  smsNotificationStatus?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLSmsNotificationStatusResponse'],
    ParentType,
    ContextType
  >;
  tdsCategories?: DashboardGraphQLResolver<Array<DashboardGraphQLResolversTypes['TDSCategory']>, ParentType, ContextType>;
  transactionById?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLTransaction'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQueryTransactionByIdArgs, 'id'>
  >;
  transactions?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLTransactionsResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQueryTransactionsArgs, 'accountNumber' | 'limit' | 'offset'>
  >;
  twoFactorPasswordEnabled?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLTwoFactorPasswordEnabledResponse'],
    ParentType,
    ContextType
  >;
  userAuthentication?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLUserAuthentication'], ParentType, ContextType>;
  userById?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLUser'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQueryUserByIdArgs, 'id'>
  >;
  validateVpa?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLValidateVpaResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQueryValidateVpaArgs, 'vpa'>
  >;
  vendorPaymentById?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLVendorPayment'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQueryVendorPaymentByIdArgs, 'id'>
  >;
  vendorPayments?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLVendorPaymentsResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQueryVendorPaymentsArgs, 'limit' | 'offset'>
  >;
  whatsappNotificationStatus?: DashboardGraphQLResolver<
    DashboardGraphQLResolversTypes['DashboardGraphQLWhatsappNotificationStatusResponse'],
    ParentType,
    ContextType,
    DashboardGraphQLRequireFields<DashboardGraphQLQueryWhatsappNotificationStatusArgs, 'source'>
  >;
  withdrawalConfig?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLWithdrawalConfig']>, ParentType, ContextType>;
  withdrawalList?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<Array<DashboardGraphQLResolversTypes['DashboardGraphQLWithdrawalsList']>>,
    ParentType,
    ContextType
  >;
  withdrawalValidity?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLWithdrawalValidity']>,
    ParentType,
    ContextType,
    Partial<DashboardGraphQLQueryWithdrawalValidityArgs>
  >;
};