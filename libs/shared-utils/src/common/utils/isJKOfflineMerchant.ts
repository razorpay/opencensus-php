export const isJKOfflineMerchant = (org = {}, user = {}) => {
  return org.isjkOrg && user.isFeatureEnabled('omni_enabled');
};
