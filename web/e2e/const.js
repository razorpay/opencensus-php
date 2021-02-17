const SELECTORS = require('./selectors');
const routes = require('./routes');

const DASHBOARD_URL = process.env.DASHBOARD_URL;
const DASHBOARD_USER = process.env.DASHBOARD_USER;
const DASHBOARD_PASSWORD = process.env.DASHBOARD_PASSWORD;

module.exports = {
  merchantEntryPattern:
    'https://(.)*cdn.(np.)?razorpay.(in|com)/dashboard((/qa)|(/funcd))?/dist/merchant-entry.js',

  routes,

  blockedIPs: [
    'tag.getdrip.com',
    'www.googletagmanager.com',
    'www.google-analytics.com',
    'apis.google.com',
    'www.redditstatic.com',
    // 'razorpay.webpush.freshchat.com,',
  ],

  SELECTORS,

  DEFAULT_TIMEOUT: process.env.DEFAULT_TIMEOUT || 100 * 1000,
  RETRY_COUNT: process.env.RETRY_COUNT,
  STATIC_PORT: process.env.STATIC_PORT || 8000,

  DASHBOARD_URL,

  creds: {
    email: DASHBOARD_USER,
    password: DASHBOARD_PASSWORD,
  },
};
