export type LADashboardUserMethods = {
  isOrgAllowedFunctionality: (feature: string) => boolean;
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
   * Checks if a certain feature is enabled for the user based on the feature name.
   *
   * Example:
   * ```ts
   * user.isFeatureEnabled('marketplace') === true
   * ```
   */
  isFeatureEnabled(feature: string): boolean;
};
