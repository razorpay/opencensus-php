export const WELCOME_TEXT_SELECTORS = {
  RESELLER_WELCOME_TEXT: 'text=Welcome to Reseller Partner dashboard, Playwright Account!',
  AGGREGATOR_WELCOME_TEXT:
    'text=Welcome to Aggregator Partner dashboard, Playwright Partner Aggregator!',
  PLATFORM_PARTNER_WELCOME_TEXT:
    'text=Welcome to Platform Partner dashboard, Playwright Partner Platform!',
};
export const CONTENT_SELECTORS = {
  CLIENTS_LIST: {
    INVITE_ACCEPTED_ON: 'text=Invite Accepted On',
    LAST_INVITED_ON: 'text=Last Invited On',
    ADDED_ON: 'text=Added On',
  },
  DETAILS_PANEL: {
    REQUEST_FOR_KYC_APPROVAL_HEADER: 'text=REQUEST KYC APPROVAL',
  },
};

export const CTA_SELECTORS = {
  FTUX_POPUPS: {
    NEW_INVITES_FLOW_GOT_IT: 'text=GOT IT',
  },
  CLIENTS_LIST: {
    PERFORM_KYC: 'text=Perform KYC',
    REQUEST_FOR_KYC: 'text=Request for KYC',
    ACCEPTED_INVITES: 'text=Accepted Invites',
    ALL_INVITES: 'text=All Invites',
  },
  DETAILS_PANEL: {
    REQUEST_FOR_KYC_ACCESS: 'text=Request for KYC access',
  },
  PRODUCT_TABS: {
    PG: '#link-header :text-is("Payments")',
    POS: '#link-header :text-is("POS")',
    CAPITAL: '#link-header :text-is("Line Of Credit")',
    X: '#link-header :text-is("X")',
  },
};
