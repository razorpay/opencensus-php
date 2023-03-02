import rolesList from './roles-list';

const {
  OWNER,
  ADMIN,
  MANAGER,
  OPERATIONS,
  FINANCE,
  SUPPORT,
  RBL_SUPERVISOR,
  SELLERAPP,
  SELLERAPP_PLUS,
  AGENT,
  REGISTRATION_LINK_AGENT,
  REGISTRATION_LINK_SUPERVISOR,
} = rolesList;

/*
 * User level map to decide whether a module can be edited by the give role.
 *
 * */
export default {
  home: [OWNER, ADMIN, MANAGER, OPERATIONS, FINANCE],
  payments: [OWNER, ADMIN, MANAGER, OPERATIONS, RBL_SUPERVISOR],
  orders: [OWNER, ADMIN, MANAGER, RBL_SUPERVISOR],
  refunds: [OWNER, ADMIN, MANAGER, OPERATIONS],
  payments_batch_uploads: [OWNER, ADMIN, MANAGER, OPERATIONS],
  refunds_batch_uploads: [OWNER, ADMIN, MANAGER, OPERATIONS],
  settlements: [OWNER, ADMIN, MANAGER, RBL_SUPERVISOR],
  invoices: [OWNER, ADMIN, MANAGER, OPERATIONS, SELLERAPP, SELLERAPP_PLUS, AGENT, RBL_SUPERVISOR],
  items: [OWNER, ADMIN, MANAGER, SELLERAPP, AGENT, SELLERAPP_PLUS],
  payment_links: [
    OWNER,
    ADMIN,
    MANAGER,
    OPERATIONS,
    SELLERAPP,
    SELLERAPP_PLUS,
    AGENT,
    RBL_SUPERVISOR,
  ],
  payment_links_batch_uploads: [
    OWNER,
    ADMIN,
    MANAGER,
    OPERATIONS,
    AGENT,
    SELLERAPP,
    SELLERAPP_PLUS,
    RBL_SUPERVISOR,
  ],
  payment_pages: [OWNER, ADMIN, MANAGER, SELLERAPP, OPERATIONS],
  payment_handle: [OWNER, ADMIN, MANAGER, SELLERAPP, OPERATIONS, FINANCE],
  payment_buttons: [OWNER, ADMIN, MANAGER, SELLERAPP, OPERATIONS],
  subscription_buttons: [OWNER, ADMIN, MANAGER, SELLERAPP, OPERATIONS],
  accounts: [OWNER, ADMIN, MANAGER],
  marketplace: [OWNER, ADMIN, MANAGER, OPERATIONS],
  subscriptions: [
    OWNER,
    ADMIN,
    MANAGER,
    OPERATIONS,
    FINANCE,
    SUPPORT,
    REGISTRATION_LINK_AGENT,
    REGISTRATION_LINK_SUPERVISOR,
  ],
  addons: [OWNER, ADMIN, MANAGER, OPERATIONS, FINANCE, SUPPORT],
  virtual_accounts: [OWNER, ADMIN, MANAGER, OPERATIONS, FINANCE],
  qr_codes: [OWNER, ADMIN, MANAGER, OPERATIONS, FINANCE],
  customers: [OWNER, ADMIN, MANAGER],
  reports: [OWNER, ADMIN, MANAGER, RBL_SUPERVISOR, AGENT],
  api_keys: [OWNER, ADMIN],
  profile: [OWNER, ADMIN, MANAGER, OPERATIONS, FINANCE, SUPPORT, SELLERAPP],
  add_funds: [OWNER, ADMIN, MANAGER, OPERATIONS],
  profile_gst: [OWNER, ADMIN, MANAGER, OPERATIONS, FINANCE, AGENT],
  credits: [OWNER, ADMIN, MANAGER],
  activation: [OWNER, ADMIN, MANAGER],
  early_settlement: [OWNER, ADMIN],
  referrals: [OWNER, ADMIN, MANAGER],
  team: [OWNER, RBL_SUPERVISOR],
  webhooks: [OWNER, ADMIN, MANAGER],
  configuration: [OWNER, ADMIN, MANAGER],
  applications: [OWNER],
  offers: [OWNER, ADMIN, MANAGER, OPERATIONS, SUPPORT, AGENT, SELLERAPP],

  // partner dashboard permissions
  submerchants: [OWNER, MANAGER, ADMIN],
  partner_applications: [OWNER, MANAGER, ADMIN],

  // optimizer
  optimizer: [OWNER, ADMIN, MANAGER, OPERATIONS],
  provider_details: [OWNER, ADMIN, MANAGER, OPERATIONS],
};
