export const PAGE_LOAD_TIMEOUT = 20 * 1000;
export const CONTENT_SELECTORS = {
  CLIENTS_LIST: {
    INVITE_ACCEPTED_ON: 'text=Invite Accepted On',
    ADDED_ON: 'text=Added On',
    ALL_INVITES: {
      LAST_INVITED_ON: 'text=Last Invited On',
      INVITE_SUCCESSFUL: 'text=Invite is resent successfully',
    },
    WELCOME_SCREEN: 'text=Welcome to Partner Dashboard',
  },
  DETAILS_PANEL: {
    REQUEST_FOR_KYC_APPROVAL_HEADER: 'text=REQUEST KYC APPROVAL',
  },
  HOME_PAGE: {
    RESELLER_PARTNER_WELCOME_TEXT:
      'text=Welcome to Reseller Partner dashboard, Playwright Account!',
    RESELLER_PARTNER_POS_WELCOME_TEXT:
      'text=Welcome to Reseller Partner dashboard, POS PLAYWRIGHT RESELLER!',
    AGGREGATOR_WELCOME_TEXT:
      'text=Welcome to Aggregator Partner dashboard, Playwright Partner Aggregator!',
    PLATFORM_PARTNER_WELCOME_TEXT:
      'text=Welcome to Platform Partner dashboard, Playwright Partner Platform!',
    START_YOUR_JOURNEY: 'span:text-is("Start your journey as Razorpay Partner")',
  },
  INVITE_MERCHANT_MODAL: {
    MODAL_HEADERS: {
      PG: 'text=Add New Clients - Razorpay Payments',
      POS: 'text=Add New Clients - Razorpay POS',
    },
    VALIDATION_MESSAGES: {
      NAME_REQUIRED: 'text=Client Name is a required field.',
      EMAIL_INVALID: 'text=Please enter a valid email id.',
      KYC_ASSIST_REQUIRED: 'text=Please select one of the options here to proceed ahead',
      TWO_CONTACTS_IDENTIFIED: 'text=2 contacts have been identified.',
    },
  },
  MANAGE_TEAM: {
    HEADER: 'text=Add your POS Partner Agents Now!',
    INVITE_NEW_MEMBER: {
      VALIDATION_MESSAGES: {
        EMAIL_INVALID: 'text=Invalid Email',
      },
    },
    INVITATIONS: {
      STATIC_INVITE: 'text=static-invitation@email.com',
      INVITE_SUCCESSFUL: 'text=Invitation resent successfully',
    },
  },
  SELECT_PRODUCT: {
    LEGACY_HEADER: 'text=Add New Merchants',
  },
  SHARE_REFERRAL_LINK_MODAL: {
    HEADER: 'div[data-blade-component="modal"] :text-is("Share Referral Link")',
  },
};

export const INPUT_SELECTORS = {
  CLIENTS_LIST: {
    ACCOUNT_ID_FILTER: 'input[name="id"]',
    EMAIL_ID_FILTER: 'input[name="email"]',
  },
  INVITE_MERCHANT_MODAL: {
    SINGLE_INVITE: {
      NAME: '.invite-merchant-form input[name="name"]',
      EMAIL: '.invite-merchant-form input[name="email"]',
      CONTACT_NO: '.invite-merchant-form input[name="contact_no"]',
    },
    BULK_INVITE: {
      FILE: '.invite-merchant-form input[type="file"]',
    },
  },
  MANAGE_TEAM: {
    INVITE_NEW_MEMBER: {
      EMAIL: 'input[name="email"]',
    },
  },
};

export const CTA_SELECTORS = {
  CLIENTS_LIST: {
    PERFORM_KYC: 'text=Perform KYC',
    REQUEST_FOR_KYC: 'text=Request for KYC',
    ACCEPTED_INVITES: 'a:text-is("Accepted Invites")',
    ALL_INVITES: 'a:text-is("All Invites")',
    SEARCH_BUTTON: 'button :text-is("Search")',
    RESEND_INVITE: 'button :text("Resend Invite")',
  },
  DETAILS_PANEL: {
    REQUEST_FOR_KYC_ACCESS: 'text=Request for KYC access',
  },
  FTUX_POPUPS: {
    NEW_INVITES_FLOW_GOT_IT: 'text=GOT IT',
  },
  HOME_PAGE: {
    REFER_NEW_CLIENT: 'text="+ Refer New Client"',
  },

  INVITE_MERCHANT_MODAL: {
    TABS: {
      SINGLE_INVITE: 'text=Using Email',
      BULK_INVITE: 'text=Bulk Upload',
      PUBLIC_LINKS: 'text=Public Link',
    },
    KYC_ASSIST: {
      YES: 'text=Yes, I will assist my client with their KYC',
      NO: 'text=No, my client will perform KYC on their own',
    },
    FOOTER_BUTTONS: {
      DOWNLOAD_SAMPLE_FILE: 'text=Download Sample File',
      NEXT_STEP: 'text=Next',
      SEND_INVITE: 'text=Send Invite',
      SEND_INVITES: 'text=Send Invites',
    },
    CLOSE_BUTTON: 'button[aria-label="Close"]',
  },
  MANAGE_TEAM: {
    INVITE_NEW_MEMBER: 'text=Invite New Member',
    SEND_INVITATION: 'text=Send Invitation',
    CLOSE_BUTTON: 'button[data-testid="modal-header-close-btn"]',
    INVITATIONS: {
      RESEND_INVITE_FOR_STATIC: '.entity-item-row-4370 button :text("Resend Invite")',
    },
  },
  PRODUCT_TABS: {
    PG: '#link-header :text-is("Payments")',
    POS: '#link-header :text-is("POS")',
    CAPITAL: '#link-header :text-is("Line Of Credit")',
    X: '#link-header :text-is("X")',
  },
  SHARE_REFERRAL_LINK_MODAL: {
    CLOSE_BUTTON: 'button[aria-label="Close"]',
    KYC_ASSIST: {
      YES: 'text=Yes, I will assist my client with their KYC',
      NO: 'text=No, my client will perform KYC on their own',
    },
  },
  SIDEBAR: {
    PARTNER_NAVLINKS: {
      MANAGE_TEAM: 'text=Manage Team',
      CLIENT_ACCOUNTS: 'text=Affiliate Accounts',
    },
  },
  SIDE_HEADER: {
    ADD_NEW_CLIENTS: 'text=Add New Clients',
    SHARE_REFERRAL_LINK: 'text=Share Referral Link',
  },
};
