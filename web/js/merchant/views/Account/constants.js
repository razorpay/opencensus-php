export const BUSINESS_TYPE_MAP = {
  1: 'Proprietorship',
  2: 'Individual',
  3: 'Partnership',
  4: 'Private',
  5: 'Public',
  6: 'LLP',
  7: 'NGO',
  9: 'Trust',
  10: 'Society',
  14: 'Government',
  15: 'Judicial Person',
  16: 'Local Authority',
  17: 'Section 8 Company'
};

export const ATTR_DETAILS = {
  name: {
    label: 'User Name',
    desc: 'This is the name of the user that you will see on you profile',
  },
  display_name: {
    label: 'Display Name',
    desc: 'This is the display name that you and your team will see on the Razorpay dashboard.',
  },
  curlec_display_name: {
    label: 'Display Name',
    desc: 'This is the display name that you and your team will see on the Curlec dashboard.',
  },
  additional_website_info: {
    label: 'Additional Business Website/App',
    desc: 'You can add second website/app to use Razorpay on that website/app',
  },
  curlec_additional_website_info: {
    label: 'Additional Business Website/App',
    desc: 'You can add second website/app to use Curlec on that website/app',
  },
  access_user_account: {
    label: 'Account Access',
    // eslint-disable-next-line prettier/prettier
    desc: 'You have access to all products and API keys. Integrate using our robust APIs or request access to products such as Subscriptions,  Route,  and Smart Collect.',
  },
  curlec_access_user_account: {
    label: 'Account Access',
    desc: 'You have access to all products and API keys',
  },
  us_access_user_account: {
    label: 'Account Access',
    desc: 'You have access to all our products and API keys. Start using Payment Links or integrate our robust APIs to get started'
  },
  restricted_access_user_account: {
    label: 'Account Access',
    // eslint-disable-next-line prettier/prettier
    desc: 'You can only access Payment Links and Invoices. Please provide website/app link to get access to our API’s and other products such as Route, Subscriptions, etc.',
  },
  curlec_restricted_access_user_account: {
    label: 'Account Access',
    // eslint-disable-next-line prettier/prettier
    desc: 'You can only access Payment Links and Invoices. Please provide website/app link to get access to our API’s and other products',
  },
  us_restricted_access_user_account: {
    label: 'Account Access',
    desc: 'You can only access Payment Links. Please provide website/app link to get access to our APIs'
  },
  billing_label: {
    label: 'Billing Label',
    desc: 'This change will also get reflected in the checkout page title.',
  },
};

export const TICKET_STATUS = {
  processing: 'Processing',
  resolved: 'Resolved',
  closed: 'Closed',
};

export const ACTIVATION_STATUS = {
  instantly_activated: 'instantly_activated',
  activated: 'activated',
  activated_mcc_pending: 'activated_mcc_pending',
};

export const MAX_FILE_SIZE_LIMIT = 52428800; // 50 MB = 100 * 1024 * 1024
