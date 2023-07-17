const { devices } = require('@playwright/test');
const { EmailCredentials, MobileCredentials, ActivatedNotIECredentials } = require('./constants');

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
    activatedNotIe: ActivatedNotIECredentials,
  };
}

export function getProjects() {
  const grep = process.env.INCLUDE_GROUPS;
  let grepInvert = process.env.EXCLUDE_GROUPS;

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
    projects.push(
      {
        name: `Login:${browser.defaultBrowserType}`,
        grep: /@flow=auth/,
        use: browser,
      },
      {
        name: 'Custom flow',
        use: browser,
        dependencies: [`Login:${browser.defaultBrowserType}`],
        grep: new RegExp(grep),
        grepInvert: new RegExp(grepInvert),
      },
    );
  });

  return projects;
}

module.exports = {
  getReporter,
  getBaseUrl,
  getCredentials,
  getProjects,
};
