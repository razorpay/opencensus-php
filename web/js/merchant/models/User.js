import ajax from 'merchant/utils/ajax';
import { filterBy } from 'rzp/utils/rzp-utils';

import { fetchFeaturesAjax } from 'merchant/modules/config';

// TODO: Rename fn. name
export function setFeatures(features) {
  let enabledFeatures = filterBy(features, 'value', true);

  return enabledFeatures;
}

export default class User {
  merchants = {};

  constructor(props) {
    Object.assign(this, props);
  }

  isFeatureEnabled(feature) {
    return (this.enabledFeatures || []).indexOf(feature.toLowerCase()) !== -1;
  }

  fetch() {
    let promise = new Promise((resolve, reject) => {
      ajax({
        url: '/user',
        appendModeInURL: false,
      })
        .then(response => {
          // Risky. fetchFeaturesAjax can make the request always in 'test'mode.
          // But hopefully, it will happen after cycle of App.js fetch User where it updatesSession with correct mode
          fetchFeaturesAjax(response.data.current)
            .catch(_ => _)
            .then(data => {
              let newUser = new User(response.data);
              newUser.features = setFeatures(
                data.success ? data.data.features : []
              );
              response.data = newUser;
              resolve(response);
            })
            .catch(err => {
              reject(err);
            });
        })
        .catch(err => reject(err));
    });

    return promise;
  }

  get userRole() {
    if (this.current && Object.keys(this.merchants).length) {
      return this.merchants[this.current].role;
    }
    return null;
  }

  get isAuthenticated() {
    return !!this.user;
  }

  get isVerified() {
    return this.user.confirmed;
  }

  /*
   * Return string of allowed roles for the given module.
   * If isReadOnly = false, then role strictly needs to have 'ALL' access.
   * Note: Since 'All' can view the route, so, by default it has isReadOnly = true for it.
   * */
  isAllowedEdit(moduleName) {
    return _isAllowed(this.userRole, moduleName, false);
  }

  isAllowedView(moduleName) {
    return _isAllowed(this.userRole, moduleName, true);
  }

  /*
  * isAllowedMultiple is for grouped tabs, example: Settings in side bar.
  * If any route is present in moduleNames, it will be treated for view only mode and will make parent group(hood) visible.
  * */
  isAllowedMultiple(moduleNames) {
    let isHoodAllowed = false;
    moduleNames = moduleNames.split(' ');

    moduleNames.forEach(m => {
      isHoodAllowed = this.isAllowedView(m);

      if (isHoodAllowed) {
        return false;
      }
    });

    return isHoodAllowed;
  }

  get isActivated() {
    return !!parseInt(this.activated);
  }

  get needsClarification() {
    return this.activation_status === 'needs_clarification';
  }

  get isSubmitted() {
    return !!parseInt(this.submitted);
  }

  get isRejected() {
    return this.activation_status === 'rejected';
  }

  get isMarketplaceEnabled() {
    return this.isFeatureEnabled('marketplace');
  }

  get isVirtualAccountsEnabled() {
    return this.isFeatureEnabled('virtual_accounts');
  }

  get isSubscriptionsEnabled() {
    return this.isFeatureEnabled('subscriptions');
  }

  get isGSTDisabled() {
    return (this.tags || []).indexOf('Gst_Invoice_Disabled') !== -1;
  }

  // TODO: Remove this code when confirmed no rollbacks
  get isNewAnalyticsEnabled() {
    return true;
  }

  get isAgentRole() {
    return this.findTag('enable_agent_role');
  }

  get enabledFeatures() {
    let pluckKey = 'feature';

    return (this.features || []).map(object => {
      return object[pluckKey];
    });
  }

  /* Check case-insensitive tag check existence */
  findTag(tag) {
    return !!this.tags.find(t => t.toLowerCase() === tag.toLowerCase());
  }

  /**
   * Detects whether user is partner or not.
   * If check has to be made for specific type of partners,
   * then send the types for which check has to be done in arguments
   */
  isPartner(...args) {
    const partnerTypes = [...args];
    return !!partnerTypes.length
      ? partnerTypes.indexOf(this.partner_type) > -1
      : !!this.partner_type;
  }

  get showEarlySettlementAnnouncement() {
    return this.activated && this.findTag('announcement_early_settlements');
  }
}

function _isAllowed(userRole, moduleName, isReadOnly) {
  if (typeof isReadOnly === undefined) {
    throw new Error('isReadOnly is required argument.');
  }

  const permissionSetForModule = MODULE_PERMISSION_MAP[moduleName];
  let isAllowed = false;

  let myRole = [];
  const roleIndex = USER_ROLES.indexOf(userRole);

  const permissionLevel = permissionSetForModule.charAt(roleIndex);

  if (permissionLevel == 2 || (isReadOnly && permissionLevel == 1)) {
    isAllowed = true;
  }

  return isAllowed;
}

const USER_ROLES = [
  'owner',
  'admin',
  'manager',
  'operations',
  'finance',
  'support',
  'sellerapp', // epos is public name
  'agent',
];

// PERMISSION Level:
// 2: All
// 1: Read only
// 0: None

/*
* Each set below is in same order as USER_ROLES
* */
const MODULE_PERMISSION_MAP = {
  home: '22222000',

  // Transactions
  payments: '22221100',
  orders: '22211100',
  refunds: '22221100',
  payments_batch_uploads: '22220000',
  refunds_batch_uploads: '22220000',

  // Settlements
  settlements: '22211100',

  // Invoices
  invoices: '22221122',
  items: '22211122',

  // Payment Links
  payment_links: '22221022',

  // Payment Pages
  payment_pages: '22211020',

  // Marketplace
  accounts: '222000000',

  // Subscriptions
  subscriptions: '22222200',
  plans: '22222200',
  addons: '22222200',

  // Smart Collect
  virtual_accounts: '22222000',

  // Customers
  customers: '22211100',

  // Reports
  reports: '22211000',
  api_keys: '22000000',

  // My Account
  profile: '22222220',
  add_funds: '22220000',
  profile_gst: '22222112',
  credits: '22211000',
  activation: '22200000',
  referrals: '22211100',
  team: '20000000',

  // Settings
  webhooks: '22200000',
  configuration: '22200000',
  applications: '20000000',
};
