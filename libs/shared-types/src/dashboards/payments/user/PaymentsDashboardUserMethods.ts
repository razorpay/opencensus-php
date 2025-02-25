export type PaymentsDashboardUserMethods = {
  isInstrumentRequestAllowed: () => boolean;
  isEditRestrictedByRazorX: () => boolean;
  isViewRestrictedByRazorX: () => boolean;
  showCSATSurvey: () => boolean;
  /**
   * Returns true if the user is allowed to manage instruments (related to financial tools).
   */
  /**
   * NPS survey feature
   */
  showNPSSurvey: () => boolean;
  showCsmExperienceSurvey: () => boolean;
  isWhatsappNotificationEnabled: () => boolean;
  /**
   * Blocks bank account update feature
   */
  blockBankAccountUpdate: () => boolean;
  /**
   * Bank account auto update or old workflow with the approval from admin
   */
  bankAccountAutoUpdateOrWorkflow: () => boolean;
  isQRCodeComingSoonEnabled: (mode: string) => boolean;
  isPartner: (...args: any) => boolean;
  isPartnerIntent: () => boolean;
  isSignUpPartnerIntent: () => boolean;
  isGoogleLogin: () => boolean;
  isICICILinkedCAFlowEnabled: () => boolean;
  isNeostoneFlowEnabled: () => boolean;
  isAllowedMultiple: (moduleNames: string) => boolean;
  isOrgFeatureExist: (feature: string) => boolean;
  isOrgAllowedFunctionality: (feature: string) => boolean;
  /**
   * Checks whether the user has certain feature flags enabled in the organization.
   */
  isOrgFeatureEnabled(feature: string): boolean;

  /**
   * Checks if the user is part of a specific experiment variant.
   *
   * Example:
   * ```ts
   * user.getExpStatus('feature_name') === 'on'
   * ```
   */
  getExpStatus(experimentName: string): string | undefined;

  /**
   * Returns true if the user is allowed to edit a specific module based on permissions.
   */
  isAllowedEdit(moduleName: string): boolean;

  /**
   * Returns true if the user is allowed to view a specific module based on permissions.
   */
  isAllowedView(moduleName: string): boolean;

  /**
   * Returns true if the user has a specific tag associated with their account.
   *
   * Example:
   * ```ts
   * user.findTag('premium_user') === true
   * ```
   */
  findTag(tag: string): boolean;

  /**
   * Checks if the product is hidden for white-labeled organizations.
   */
  isProductHiddenForWhiteLabelledOrg(moduleName: string): boolean;

  /**
   * Checks if a certain feature is enabled for the user based on the feature name.
   *
   * Example:
   * ```ts
   * user.isFeatureEnabled('marketplace') === true
   * ```
   */
  isFeatureEnabled(feature: string): boolean;
  isOptimizerView: () => boolean;
};
