const { devices } = require('@playwright/test');
const {
  getEmailCredentials,
  getMobileCredentials,
  getActivatedNotIECredentials,
  getMagicCheckoutCredentials,
  getPosCredentials,
  getCurlecCredentials,
} = require('../constants/constants');

// use report portal for CI, and html for development
function getReporter() {
  const isCi = process.env.CI === 'true';

  const reportPortalConfig = {
    token: process.env.REPORT_PORTAL_TOKEN,
    endpoint: `${process.env.REPORT_PORTAL_HOST}/api/v1`,
    project: process.env.REPORT_PORTAL_PROJECT,
    launch: `${process.env.REPO_NAME}`,
    skippedIssue: false,
    attributes: [
      {
        key: 'build',
        value: process.env.COMMIT_ID,
      },
    ],
  };
  if (isCi) {
    return [['@reportportal/agent-js-playwright', reportPortalConfig]];
  }

  // If we use dotenv, the ENV is not updated when we import the baseConfig from universe, so we're doing this again (for E2E_REPORTERS)
  return [
    [
      'html',
      // Remove to disable auto-open on failure.
      // {
      //   open: 'never',
      // },
    ],
  ];
}

function getBaseUrl(customDomain) {
  const baseUrl = process.env.E2E_BASE_URL || 'https://dashboard.dev.razorpay.in';
  const label = process.env.DEVSTACK_LABEL;
  const url = new URL(baseUrl);
  const subDomain = url.hostname.split('.')[0];

  if (label) {
    const newSubDomainURL = customDomain
      ? `${subDomain}-${label}-${customDomain}`
      : `${subDomain}-${label}`;
    return baseUrl.replace(subDomain, newSubDomainURL);
  }

  const newSubDomainURL = customDomain ? `${subDomain}-${customDomain}` : `${subDomain}`;
  return baseUrl.replace(subDomain, newSubDomainURL);
}

function getCredentials() {
  return {
    emailCred: getEmailCredentials(),
    mobileCred: getMobileCredentials(),
    activatedNotIe: getActivatedNotIECredentials(),
    magicCheckout: getMagicCheckoutCredentials(),
    posCredentials: getPosCredentials(),
    curlecCred: getCurlecCredentials(),
  };
}

export function getProjects({ projectType, isCurlec = true }) {
  let grep = process.env.INCLUDE_GROUPS;
  let grepInvert = process.env.EXCLUDE_GROUPS;

  if (projectType === 'Login') {
    grep = /@flow=auth/;
  }

  // when both values are empty, run non-auth flows (usually happens when run locally)
  if (!grep && !grepInvert) {
    grepInvert = /@flow=auth/;
  }

  const browsers = [devices['Desktop Chrome']];
  const projects = [];

  if (process.env.GIT_BRANCH === 'master') {
    browsers.push(devices['Desktop Firefox'], devices['Desktop Safari']);
  }

  browsers.forEach((browser) => {
    projects.push({
      name: `${projectType} E2E flow: Executes on ${browser.defaultBrowserType}`,
      use: browser,
      grep: grep ? new RegExp(grep) : undefined,
      grepInvert: grepInvert ? new RegExp(grepInvert) : undefined,
    });
    if (isCurlec) {
      projects.push({
        name: `${projectType} E2E flow for Curlec: Executes on ${browser.defaultBrowserType}`,
        grep: projectType === 'Login' ? /@flow=MY-auth/ : /@country=MY/,
        grepInvert: grepInvert ? new RegExp(grepInvert) : undefined,
        use: {
          ...browser,
          baseURL: getBaseUrl('curlec'),
        },
      });
    }
  });

  return projects;
}

module.exports = {
  getReporter,
  getBaseUrl,
  getCredentials,
  getProjects,
};
