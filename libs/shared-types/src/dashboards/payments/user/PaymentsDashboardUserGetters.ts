import type { RazorpayUser } from '../../../common';

export interface PaymentsDashboardUserGetters {
  /**
   * The role of the current user in the context of the merchant.
   * Possible roles include 'admin', 'owner', etc.
   *
   * Example:
   * ```ts
   * user.userRole === 'owner'
   * ```
   */
  userRole: string | null;

  /**
   * Indicates whether the user is authenticated (i.e., whether a user object is present).
   */
  isAuthenticated: boolean;

  /**
   * Indicates whether the user has confirmed their account or identity (email/phone verification).
   */
  isVerified: boolean;

  /**
   * The custom code of the organization the user belongs to (e.g., 'rzp', 'axis', etc.).
   *
   * Example:
   * ```ts
   * user.orgCustomCode === 'rzp'
   * ```
   */
  orgCustomCode: string | undefined;

  /**
   * Returns whether the organization is white-labeled, which indicates that it is not Razorpay or Curlec.
   */
  isWhiteLabelledOrg: boolean;

  /**
   * Returns true if the organization is Razorpay, false otherwise.
   */
  isOrgRZP: boolean;

  /**
   * Returns true if the organization is Axis Bank.
   */
  isOrgAxis: boolean;

  /**
   * Returns true if the organization is Kotak Mahindra Bank.
   */
  isOrgKotak: boolean;

  /**
   * Returns true if the organization is Curlec.
   */
  isOrgCurlec: boolean;

  /**
   * Gets the activation flow information for the user (e.g., 'whitelist', 'blacklist').
   *
   * Example:
   * ```ts
   * user.instantActivation.isWhitelistFlow === true
   * ```
   */
  instantActivation: {
    isUnregisteredBusiness: boolean;
    isInstantActivationEnabled: boolean;
    isWhitelistFlow: boolean;
    isBlacklistFlow: boolean;
    isGraylistFlow: boolean;
    isL1Submitted: boolean;
  } & Pick<RazorpayUser, 'activation_flow' | 'business_type' | 'activated'>;

  /**
   * Returns true if the PayPal payment method is enabled for the user.
   */
  isPayPalEnabled: boolean;

  /**
   * Returns true if the user needs to clarify their activation status.
   */
  needsClarification: boolean;

  /**
   * Returns true if the user's activation is pending due to the MCC (Merchant Category Code).
   */
  isActivatedMCCPending: boolean;

  /**
   * Checks if payments are enabled for the user based on their activation status.
   */
  isPaymentsEnabled: boolean;

  /**
   * Returns true if subscriptions are enabled for the user.
   */
  isSubscriptionsEnabled: boolean;

  /**
   * Returns true if QR codes are enabled for the user.
   */
  isQRCodeProductEnabled: boolean;

  /**
   * Returns true if virtual accounts are enabled for the user.
   */
  isVirtualAccountsEnabled: boolean;

  /**
   * Returns the user's enabled features.
   *
   * Example:
   * ```ts
   * user.enabledFeatures === ['marketplace', 'subscriptions']
   * ```
   */
  enabledFeatures: string[];

  /**
   * Returns true if the user has been activated.
   */
  isActivated: boolean;

  /**
   * Returns true if the user is in a restricted merchant status.
   */
  isMerchantRestricted: boolean;

  /**
   * Returns true if the user is an owner.
   */
  isOwner: boolean;

  /**
   * Returns true if the user is an admin or owner.
   */
  isAdminOrOwner: boolean;

  /**
   * Returns true if the user is part of the support team.
   */
  isSupportRole: boolean;

  /**
   * Returns the currently active merchant for the user.
   *
   * Example:
   * ```ts
   * user.currentMerchant === { merchantDetails }
   * ```
   */
  currentMerchant: Record<string, any> | undefined;

  /**
   * Returns true if the 2-factor authentication (2FA) is enabled for the user's account.
   */
  isTwoFactorVerified: boolean;

  /**
   * Returns true if the 2-factor authentication setup has been completed by the user.
   */
  isTwoFactorSetupDone: boolean;

  /**
   * Returns true if instrument requests are hidden by the organization.
   */
  isInstrumentRequestHidden: boolean;

  /**
   * Returns true if the user is part of the Razorpay ecosystem downtime experiment.
   */
  isEcosystemDowntimeEnabled: boolean;

  /**
   * Returns true if GST is disabled for the user.
   */
  isGSTDisabled: boolean;

  /**
   * Returns true if refunds are disabled for the user.
   */
  isRefundsDisabled: boolean;

  /**
   * Returns true if the merchant is allowed to use Razorpay checkout.
   */
  isCheckoutAnalyticsEnabled: boolean;

  /**
   * Returns true if the API keys revamp feature is enabled for the user.
   */
  isApiKeysRevampEnabled: boolean;

  secondFactorAuthOfCurrentMerchant: boolean | ((secondFactorAuth: boolean) => void);
  secondFactorAuthOfUser: boolean | ((secondFactorAuth: boolean) => void);

  isContactMobileChangeAllowed: boolean;
  internationalActivationFlow: boolean;
  isAccepted: boolean;
  isSubmitted: boolean;
  isRejected: boolean;
  isUnderReview: boolean;
  isMarketplaceEnabled: boolean;
  isCovidFeatureEnabled: boolean;
  isRepaymentBannerEnabled: boolean;
  isMagicCheckoutEnabled: boolean;
  isMagicKonnectEnabled: boolean;
  isMerchantExpiryPPEnabled: boolean;
  isCustomerAmountEnabled: boolean;
  isCbMkycMerchant: boolean;
  isCbImportMerchant: boolean;
  isCreateOwnTemplateEnabled: boolean;
  isRiskAndFraudEnabled: boolean;
  isNoExpiryMandatoryPP: boolean;
  showPayerNamePP: boolean;
  showCustomTemplatePP: boolean;
  hideDynamicPriceFieldPP: boolean;
  isBulkAddressUploadEnabled: boolean;
  isMagicCheckoutLive: boolean;
  isShipRocketEnabled: boolean;
  isMagicSettingsEnabled: boolean;
  isMagicRTOAnalyticsV3Enabled: boolean;
  isMagicPrepayCODEnabled: boolean;
  isMagicOrderAnalyticsEnabled: boolean;
  isMagicOrderAnalyticsCREnabled: boolean;
  isMagicShopifyOrderEditEnabled: boolean;
  isMagicCODOrderAutomationEnabled: boolean;
  isCustomerTrustEnabled: boolean;
  isCardMultipleFrequencyEnabled: boolean;
  isDebitPatternEnabled: boolean;
  isMagicCODEngineEnabled: boolean;
  isShopifyMagicEnabled: boolean;
  isMagicWoocEnabled: boolean;
  isPaymentPagesEnabled: boolean;
  isPaymentLinksEnabled: boolean;
  isInvoicesEnabled: boolean;
  isOffersEnabled: boolean;
  isAffordabilityWidgetEnabled: boolean;
  isPaymentHandleEnabled: boolean;
  isRewardsEnabled: boolean;
  isPaymentButtonsEnabled: boolean;
  isStoresEnabled: boolean;
  isQRCodeDedicatedTerminalEnabled: boolean;
  isRazorxRXCASelfServeFlowEnabled: boolean;
  isRewardsPageEnabled: boolean;
  isPaymentPageEmailOptional: boolean;
  isPaymentPageContactOptional: boolean;
  isPaymentPageCustomDomainEnabled: boolean;
  isPaymentPageFileUploadEnabled: boolean;
  isEsOnDemandBlocked: boolean;
  isPaymentPageMagicEnabled: boolean;
  isPaymentPageOnboardingRedirectionEnabled: boolean;
  isPaymentPageCustomDomainShowRemoveEnabled: boolean;
  isPaymentPageStorefrontEnabled: boolean;
  isInvoiceCreateFlowUXOptimizationEnabled: boolean;
  isRTBProgramEnabled: boolean;
  isDisputePresentmentEnabled: boolean;
  isCashAdvanceDisabled: boolean;
  isLoansDisabled: boolean;
  isProjectNitroEnabled: boolean;
  isDeveloperConsoleEnabled: boolean;
  isDeveloperConsoleWebhooksTabEnabled: boolean;
  isCatalystBannerFL: boolean;
  isCatalystBannerEF: boolean;
  isCatalystBannerG: boolean;
  isShowRazorpayXWidgetEnabled: boolean;
  isProjectMoonshineEnabled: boolean;
  isWhatsNewLazyEnabled: boolean;
  isCSSEducationEnabled: boolean;
  isCSSOtherBusinessesEnabled: boolean;
  isLoanCustomAmountRepaymentEnabled: boolean;
  isPartOfZapierIntegrationExperiment: boolean;
  is2FAMobileSignupEnabled: boolean;
  faTextVariant: boolean;
  showFAPaymantCount: boolean;
  isFAEnabled: boolean;
  getMaxFAMtv: number;
  isChargeAtWillEnabled: boolean;
  isTPVEnabled: boolean;
  isEsignEnabled: boolean;
  isAgentRole: boolean;
  isWorkboxEnable: boolean;
  isMobileSignupCareActive: boolean;
  isSmartDashboardActive: boolean;
  isRazorxAnnouncementEnabled: boolean;
  isInvoiceReceiptMandatory: boolean;
  isSGCountry: boolean;
  isINCountry: boolean;
  isRBLRoleEnabled: boolean;
  isRegistrationLinkRoleEnabled: boolean;
  isTestModeBlocked: boolean;
  isIssuingDashboardEnabled: boolean;
  isIssuingBulkUploadEnabled: boolean;
  isIssuingGcmsEnabled: boolean;
  isOmniEnabledMerchant: boolean;
  isRegistrationLinkTokenAndPaymentsEnabled: boolean;
  isRegistrationLinkBatchUploadEnabled: boolean;
  isRegistrationLinkBasedRole: boolean;
  isRegistrationLinkSupervisorRole: boolean;
  showInstantActivation: boolean;
  isMinimumFirstPaymentEnabled: boolean;
  isPBDirectPluginLinks: boolean;
  isHavingPartnerConfigs: boolean;
  isHavingSubventionConfigs: boolean;
  isOptimizerRZPVASEnabled: boolean;
  isOptimizerEnabled: boolean;
  isOptimizerOnboardingEnabled: boolean;
  isSodexoInstrumentEnabled: boolean;
  isPaytmAutoDebitEnabled: boolean;
  isHidePIDetails: boolean;
  isCareHealthOwner: boolean;
  hideForNIASupportRole: boolean;
  isPayerNameEnabled: boolean;
  isSingleReconEnabled: boolean;
  isOndemandSettlementEnabled: boolean;
  isOndemandRouteSettlementsEnabled: boolean;
  isSupportDetails2FAEnabled: boolean;
  isComdelApiEnabled: boolean;
  isFdTicketsEnabled: boolean;
  isTicketCreationFlowRevamp: boolean;
  isAnnouncementIconEnabled: boolean;
  isWebsiteComplianceFlowEnabled: boolean;
  isWebsiteComplianceModalNonDismissible: boolean;
  isWhatsNewSectionEnabled: boolean;
  isUxRevampPhase2Enabled: boolean;
  iscaptureSettingsRevampEnabled: boolean;
  isFeeCreditSelfServeEnabled: boolean;
  isRefundCreditSelfServeEnabled: boolean;
  isRefundSourceFallbackEnabled: boolean;
  isSettlementDashboardVisibilityEnabled: boolean;
  isReserveBalanceSelfServeEnabled: boolean;
  isFtxEnabled: boolean;
  isEmailSelfServeEnabled: boolean;
  isCovidReliefFlowEnabled: boolean;
  isFeeBearerSelfServeOn: boolean;
  isAutomaticSettlementEnabled: boolean;
  isAutomaticSettlementRestricted: boolean;
  isOndemandSettlementsRestricted: boolean;
  isWebsiteSelfServeOn: boolean;
  isTransactionLimitUpdateSelfServeOn: boolean;
  isAdditionalDomainWhitelistSelfServeOn: boolean;
  isCreditPullEnabled: boolean;
  isDiwaliPromoEnabled: boolean;
  isRefundAllowed: boolean;
  isISBannerEnabled: boolean;
  isCapitalBannerEnabled: boolean;
  isExpireByRequired: boolean;
  isInstantActivationEnabled: boolean;
  isInstantActivationVideoEnabled: boolean;
  isFeEasyDashboardNCEnabled: boolean;
  isAddReplyMigrationActive: boolean;
  isInttCurrenciesEnabled: boolean;
  getPaymentLinkCustomizedFormFields: boolean;
  isRegAutoKYCEnabled: boolean;
  getCurrencyList: boolean;
  plDefaultExpiryTime: boolean;
  isEnhancedEPOSEnabled: boolean;
  isMobileHotjarSurveyEnabled: boolean;
  isNPSSurveyBannerEnabled: boolean;
  isShowCommissionBalanceEnabled: boolean;
  isFirstAmountHidden: boolean;
  paymentLinkCreationFormExtraFields: boolean;
  isAllowedTeamManagement: boolean;
  isCustomNotesDropdownEnabled: boolean;
  isPaymentLinkCustomerNameFieldEnabled: boolean;
  isPaymentLinkBatchEnabledForSellerAppRole: boolean;
  isPaymentLinkDescriptionRequired: boolean;
  isBatchCancelEnabled: boolean;
  isVACreationBankAccountDisabled: boolean;
  isCompanyNameHiddenRazorX: boolean;
  isVirtualVPAPrefixEnabled: boolean;
  missedOrderPLBanner: boolean;
  isPaymentsExtraRefundDetailsEnabled: boolean;
  isEmandateNonzeroAmountEnabled: boolean;
  isSubscriptionOffersEnabled: boolean;
  isSubscriptionOffersReportsEnabled: boolean;
  isCAWRecurringChargeAxisEnabled: boolean;
  isPaymentButtonEnabledByRazorX: boolean;
  isCriticalRouteExperimentEnabled: boolean;
  isBatchSchedulingOptionsExperimentEnabled: boolean;
  isDirectTransferEnabled: boolean;
  isSubscriptionButtonEnabled: boolean;
  isBharatQREnabled: boolean;
  isPaymentLinkCreationV2Enabled: boolean;
  isRefundPendingStatusEnabled: boolean;
  isSellerAppRole: boolean;
  isPartnerAgentRole: boolean;
  isPartnerRole: boolean;
  isRouteCodeSupportEnabled: boolean;
  isOdsMigrationEnabled: boolean;
  isRouteLinkedAccountCreationDisabled: boolean;
  isPLSwitchEnabled: boolean;
  isOnboardingV2Enabled: boolean;
  isActivationMccPendingProgressbarDisabled: boolean;
  isBDAndAovEnabled: boolean;
  isAdharEkycRequired: boolean;
  isAdharEkycRequiredForTrustSocietyNgo: boolean;
  isInternalStatusPageEnabled: boolean;
  isPaymentPageReceiptsEnabled: boolean;
  isPaymentPageDescriptionRequired: boolean;
  isPaymentlinksV2Enabled: boolean;
  isPaymentlinksV2CompatEnabled: boolean;
  isNonFldgLoansEnabled: boolean;
  isLoansEnabled: boolean;
  isLOSEnabled: boolean;
  isLOCEnabled: boolean;
  isCashAdvanceStage1Enabled: boolean;
  isCashAdvanceStage2Enabled: boolean;
  isLocCliOfferEnabled: boolean;
  isWithdrawFeatureEnabled: boolean;
  isCashOnCardEnabled: boolean;
  isLOCEMIEnabled: boolean;
  isNetBankingEnabled: boolean;
  isCardsLOSEnabled: boolean;
  isCardsEnabled: boolean;
  isUnregisteredBusiness: boolean;
  isCommissionInvoicesEnabled: boolean;
  isPLBatchUploadEnabled: boolean;
  isBbpsEnabled: boolean;
  isPlV2DisableAllSmsEnabled: boolean;
  isPlV2DisableAllEmailEnabled: boolean;
  isPlV2DisableReminderSmsEnabled: boolean;
  isPlV2DisableReminderEmailEnabled: boolean;
  isAppSwitcherEnabled: boolean;
  isSourceRX: boolean;
  showOnDemandDeduction: boolean;
  isAutomatedLOCEligible: boolean;
  isNPSAnnouncementPG1m: boolean;
  isNPSAnnouncementPG6m: boolean;
  isNPSAnnouncementPL: boolean;
  isMerchantExpiryPL: boolean;
  isNocodeappFeeApplicable: boolean;
  isEmailMandatoryOnL1: boolean;
  isEmailNonMandatoryOnL1: boolean;
  isEmailNonMandatoryOnL2Form: boolean;
  isNPSAnnouncementPP: boolean;
  isVAAccountOnSCMigration: boolean;
  isLinkAccountEnabled: boolean;
  isAppStoreEnabled: boolean;
  isPartnershipFUX: boolean;
  isPartnershipNPS: boolean;
  isSubMerchantKycEnabled: boolean;
  canSkipPoiValidation: boolean;
  canGenerateTnCPage: boolean;
  isL2AllowedForPoiInitiated: boolean;
  isAadharEkycMandatory: boolean;
  isGstinMandatory: boolean;
  isGstinAddFlowEnabled: boolean;
  isGstinEditFlowEnabled: boolean;
  isSyncExperimentEnabled: boolean;
  isCreditSelfServeDisabled: boolean;
  isOnboardingCouponEnabled: boolean;
  autoOpenOnboardingCoupon: boolean;
  isIndependentPartnerKYCEnabled: boolean;
  isAutoRefreshExperimentEnabled: boolean;
  isSyncBankVerificationEnabled: boolean;
  isProductRecommendationEnabled: boolean;
  isLoansCollectionsEnabled: boolean;
  isAutoPLEnabled: boolean;
  isGstinAutoPopulate: boolean;
  showL1FormOnLogin: boolean;
  autoOpenL1Form: boolean;
  isGstinLLpinCinSyncFlowEnabled: boolean;
  isGstinSyncFlowEnabled: boolean;
  isLlpinSyncFlowEnabled: boolean;
  isCinSyncFlowEnabled: boolean;
  autoOpenL2Form: boolean;
  isLiteOnboarding: boolean;
  isUpdatedLiteOnboarding: boolean;
  isActivationFormFullView: boolean;
  isMsmeCertificateEnabled: boolean;
  isDigilockerEkyc: boolean;
  isOnboardAsResellers: boolean;
  isShowInvoiceCurrentFY: boolean;
  isShowPayrollWidgetEnabled: boolean;
  isShowAffordabilityWidget: boolean;
  isShowAffWidgetShopifyWaitlist: boolean;
  isShowAffWidgetWoocWaitlist: boolean;
  isShowSegregatedCreditEmi: boolean;
  isVasTestingMerchant: boolean;
  isAccountAndSettingsRevampEnabled: boolean;
  isPaymentHandleSplitzEnabled: boolean;
  isBundlePricingEnabled: boolean;
  isProductLedOnboarding: boolean;
  isBankAccountUpdateRevampEnabled: boolean;
  isGetTicketApiMigration: boolean;
  isContactDetailsRevamp: boolean;
  isUserNameUpdateEnabled: boolean;
  showTerminalStatusBanner: boolean;
  isProductLedOnboardingRZP: boolean;
  isIERevampEnabled: boolean;
  isUniversalSearchEnabled: boolean;
  isSearchv2Phase1Enabled: boolean;
  isFetchTicketsApiMigration: boolean;
  isPartnershipForCapitalEnabled: boolean;
  isRevokeApplicationEnabled: boolean;
  isSettlementV3RevampEnabled: boolean;
  isPartnershipForPhantomEnabled: boolean;
  isCustomReportExtensionsEnabled: boolean;
  isInternationalMethodsHidden: boolean;
  isShowInternationalPaymentBtnExpEnabled: boolean;
  isFtuxEnabled: boolean;
  showIsPlusPlusExperiment: boolean;
  isSrAdminEnabled: boolean;
  isDynamicPlOffset: boolean;
  isLRSEducationFlow: boolean;
  isCustomTransactionTabView: boolean;
  isHideMonthlyInvoiceEnabled: boolean;
  isCustomMerchantUPIQR: boolean;
  isOmniChannelMerchant: boolean;
  isMultiCouponsEnabled: boolean;
  isRRNSearchEnabled: boolean;
  isAssistedOnboardingMerchant: boolean;
  isJnKOmniEnabled: boolean;
  isPgLegderReverseShadowEnabled: boolean;
  isCardRefundDisabled: boolean;
  isNetbankingRefundDisabled: boolean;
  isUpiRefundDisabled: boolean;
  isProductTourScreenHidden: boolean;
  isVASOrg: boolean;
  isPaymentReceiptCustomizerEnabled: boolean;
  isPosOrderIDEnabled: boolean;
}
