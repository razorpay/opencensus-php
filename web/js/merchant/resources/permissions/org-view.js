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
  'payment_pages',
  'payment_links',
  'configuration',
  'add_funds',
];

const HDFC_restrictedFeatures = [
  'flashcheckout',
  'monthlyInvoice',
  'current_balance',
  'card_refunds',
  'external_links',
];

/*
* Map of orgs having restrictions on corresponding modules/features
* */

export const antiOrgsModules = {
  hdfc: HDFC_restrictedModules,
};

export const antiOrgsFeatures = {
  hdfc: HDFC_restrictedFeatures,
};
