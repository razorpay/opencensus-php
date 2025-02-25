
export type LADashboardUserGetters = {
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

  isAllowedLARefunds: boolean;

  /**
   * Returns the user's enabled features.
   *
   * Example:
   * ```ts
   * user.enabledFeatures === ['marketplace', 'subscriptions']
   * ```
   */
  enabledFeatures: string[];

  isShowParentPaymentIdEnabled: string | undefined;

  /**
   * Returns whether the organization is white-labeled, which indicates that it is not Razorpay or Curlec.
   */
  isWhiteLabelledOrg: boolean;

  isNewAnalyticsEnabled: boolean;
  isSubmitted: boolean;
};
