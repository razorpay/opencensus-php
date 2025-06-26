export const isOneHomeDevSetup = true;

export const PRODUCT_ALIAS_MAP = {
  PAYMENTS: 'payments_top_navigation_item',
  BANKING: 'banking_top_navigation_item',
  PAYROLL: 'payroll_top_navigation_item',
  RIZE: 'rize_top_navigation_item',
  PARTNERS: 'partners_top_navigation_item',
  HOME: 'home_top_navigation_item',
  COMPANY_REGISTRATION: 'company_registration_top_navigation_item',
} as const;

export const PRODUCT_PATH_MAP = {
  [PRODUCT_ALIAS_MAP.BANKING]: '/banking/*',
  [PRODUCT_ALIAS_MAP.PARTNERS]: '/partners/*',
  [PRODUCT_ALIAS_MAP.COMPANY_REGISTRATION]: '/company-registration/*',
  [PRODUCT_ALIAS_MAP.HOME]: '/home',
};

export const PRODUCT_PATH_MAP_FOR_INTERNAL_NAVIGATION = {
  [PRODUCT_ALIAS_MAP.BANKING]: '/banking',
  [PRODUCT_ALIAS_MAP.PAYMENTS]: '/dashboard',
};

export const PRODUCT_SEARCH_SUPPORT_FOR_MOBILE = [PRODUCT_ALIAS_MAP.PAYMENTS];

/**
 * Registry to store full screen flows from different products/remotes
 * This helps maintain a centralized list of routes that should hide navigation
 */
export const FULL_SCREEN_FLOWS_REGISTRY = {
  // Banking related full screen flows
  BANKING: [
    // Pattern-based matching for common flow patterns using React Router compatible syntax
    '/banking/createPayout/*',
    '/banking/invoice/*',
    '/banking/create-invoice/*',
    '/banking/createAdvance/*',
    '/banking/createPurchaseOrder/*',
    '/banking/createGrn/*',
    '/banking/create-role/*',
    '/banking/add-team-member/*',
    '/banking/add-team-member-v2/*',
    '/banking/update-team-member-role/*',
    '/banking/workflow/*',
    '/banking/payout-links/*',
    '/banking/vendor-payments/vendor-application/*',
    '/banking/vendor-payments/vendors-onboarding/*',
    '/banking/bulk-payout/create/*',
    '/banking/bulk-upload/create/*',
    '/banking/bulk-vendors/create/*',
    '/banking/demo/*',
    '/banking/contacts/create/*',

    // Individual routes that don't fit patterns
    '/banking/payouts/create',
    '/banking/create-invoice',
    '/banking/selectInvoice',
    '/banking/searchContact',
    '/banking/load-funds',
    '/banking/edit-role',
    '/banking/access-denied',
    '/banking/demo-access-denied',
    '/banking/support',
    '/banking/budgets-create',
    '/banking/budgets-edit',
    '/banking/tally-onboarding',
    '/banking/gst-branches-mapping',
    '/banking/accounting-categorise-flow',
  ],

  // Add additional product flow lists here
  // PAYMENTS: [],
};

/**
 * Helper function to get all registered full screen flows
 * @returns {string[]} Flattened array of all full screen flows
 */
export const getAllFullScreenFlows = (): string[] => {
  return Object.values(FULL_SCREEN_FLOWS_REGISTRY).flat();
};
