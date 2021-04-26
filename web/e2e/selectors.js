const MY_ACCOUNT = require('./tests/MyAccount/selectors');
const SETTINGS = require('./tests/Settings/selectors');

module.exports = {
  LOGIN: {
    NORMAL_LOGIN_EMAIL: '.login-details-translate-container form #email',
    NORMAL_LOGIN_PASSWORD: '.login-details-translate-container form input[name="password"]',
    NORMAL_LOGIN_SUBMIT: '.login-details-translate-container form [type="submit"]',
  },
  HOME: {
    LOGO: '.brand-logo [href="/app/dashboard"]',
  },
  SIDEBAR_LINKS: {
    HOME: '.sidebar >> text="Home"',
    TRANSACTIONS: '.sidebar >> text="Transactions"',
    SETTLEMENTS: '.sidebar >> text="Settlements"',
    INVOICES: '.sidebar >> text="Invoices"',
    PAYMENTLINKS: '.sidebar >> text="Payment Links"',
    PAYMENTPAGES: '.sidebar >> text="Payment Pages"',
    ROUTE: '.sidebar >> text="Route"',
    SUBSCRIPTIONS: '.sidebar >> text="Subscriptions"',
    SMARTCOLLECT: '.sidebar >> text="Smart Collect"',
    CUSTOMERS: '.sidebar >> text="Customers"',
    OFFERS: '.sidebar >> text="Offers"',
    REPORTS: '.sidebar >> text="Reports"',
    MY_ACCOUNT: '.sidebar >> text="My Account"',
    SETTINGS: '.sidebar >> text="Settings"',
  },
  MY_ACCOUNT,
  SETTINGS,
  ACCOUNT_ACTIVATION: {},
};
