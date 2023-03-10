const { EmailCredentials, MobileCredentials } = require('./constants');

function getBaseUrl() {
  const baseUrl = process.env.E2E_BASE_URL || 'https://dashboard.dev.razorpay.in';
  const label = process.env.DEVSTACK_LABEL;
  if (label) {
    const url = new URL(baseUrl);
    const subDomain = url.hostname.split('.')[0];
    return baseUrl.replace(subDomain, `${subDomain}-${label}`);
  }
  return baseUrl;
}

function getCredentials() {
  return {
    emailCred: EmailCredentials,
    mobileCred: MobileCredentials,
  };
}

module.exports = {
  getBaseUrl,
  getCredentials,
};
