/*
 * Org level map to decide whether a module is allowed for the given Org.
 * This is Anti-map for ease, so it lists only the not-allowed modules.
 * This supercedes the role-level permissions
 * */
const HDFC_restrictedModules = [
  'subscriptions',
  'accounts',
  'marketplace',
  'invoices',
  'virtual_accounts',
  // 'paymentpages'
];

const antiOrgsPermissions = {
  rzp: [], // All modules are allowed
  hdfc: HDFC_restrictedModules, // These modules are not allowed for this org
};

export default antiOrgsPermissions;
