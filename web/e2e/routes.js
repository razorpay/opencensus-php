const DASHBOARD_URL = process.env.DASHBOARD_URL;

module.exports = {
  LOGIN: `${DASHBOARD_URL}#/access/signin`,
  HOME: `${DASHBOARD_URL}app/dashboard`,
  PAYMENTS: `${DASHBOARD_URL}app/payments`,
  REFUNDS: `${DASHBOARD_URL}app/refunds`,
  BATCH_REFUNDS: `${DASHBOARD_URL}app/refunds/batchuploads`,
  ORDERS: `${DASHBOARD_URL}app/orders`,
  SETTINGS: `${DASHBOARD_URL}app/config`,
  MY_ACCOUNT: `${DASHBOARD_URL}app/profile`,
  APP_KEYS: `${DASHBOARD_URL}app/keys`,
  TICKETS_PAGE: `${DASHBOARD_URL}app/ticket-support/tickets`,
};
