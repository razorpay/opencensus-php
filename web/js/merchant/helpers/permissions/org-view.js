/*
 * Org level map to decide whether a module is allowed for the given Org.
 * This is Anti-map for ease, so it lists only the not-allowed modules.
 * This supercedes the role-level permissions
 * */
const HDFC_restrictedModules = [
  'subscriptions',
  'invoices',
  'virtual_accounts',
  'payment_pages',
  'configuration',
  'add_funds',
  'profile_gst',
];

const HDFC_restrictedFeatures = ['flashcheckout', 'current_balance', 'external_links'];

const Bajaj_restrictedModules = [
  'marketplace', //internal name for route feature
  'accounts',
  'virtual_accounts',
  // enabling PP for bajaj temporary change
  // 'payment_pages',
  'invoices',
];

const Bajaj_restrictedFeatures = ['monthlyInvoice', 'external_links'];

/*
 * Map of orgs having restrictions on corresponding modules/features
 * */

export const antiOrgsModules = {
  hdfc: HDFC_restrictedModules,
  bajaj: Bajaj_restrictedModules,
};

export const antiOrgsFeatures = {
  hdfc: HDFC_restrictedFeatures,
  bajaj: Bajaj_restrictedFeatures,
};
