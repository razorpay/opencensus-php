import { devices } from '@playwright/test';
import path from 'path';

// use report portal for CI, and html for development
export const getReporter = () => {
  const isCi = process.env.CI === 'true';
  const playwrightAnalysisFolder = path.resolve(process.cwd(), '.playwright-analysis/analysis');

  return [
    isCi && [
      '@reportportal/agent-js-playwright',
      {
        apiKey: process.env.REPORT_PORTAL_TOKEN,
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
      },
    ],
    isCi && ['dot'],
    [
      'html',
      {
        outputFolder: playwrightAnalysisFolder,
      },
    ],
  ].filter(Boolean);
};

export const getBaseUrl = (customDomain?: string) => {
  const baseUrl = process.env.E2E_BASE_URL || 'https://dashboard.dev.razorpay.in';
  const label = process.env.DEVSTACK_LABEL;
  const url = new URL(baseUrl);
  const subDomain = url.hostname.split('.')[0];

  if (label) {
    const newSubDomainURL = Boolean(customDomain)
      ? `${subDomain}-${label}-${customDomain}`
      : `${subDomain}-${label}`;
    return baseUrl.replace(subDomain, newSubDomainURL);
  }

  const newSubDomainURL = customDomain ? `${subDomain}-${customDomain}` : `${subDomain}`;
  return baseUrl.replace(subDomain, newSubDomainURL);
};

export const getProjects = ({
  projectType,
  isCurlec = true,
}: {
  projectType: string;
  isCurlec?: boolean;
}) => {
  let grep: RegExp | string | undefined = process.env.INCLUDE_GROUPS;
  let grepInvert: RegExp | string | undefined = process.env.EXCLUDE_GROUPS;

  if (projectType === 'Login') {
    grep = /@flow=auth/;
  }

  // when both values are empty, run non-auth flows (usually happens when run locally)
  if (!grep && !grepInvert) {
    grepInvert = /@flow=auth/;
  }

  const browsers = [devices['Desktop Chrome']];
  const projects: any[] = [];

  // We are running e2es on chrome only for now. Modify Dockerfile.e2e if using multiple browsers
  // if (process.env.GIT_BRANCH === 'master') {
  //   browsers.push(devices['Desktop Firefox'], devices['Desktop Safari']);
  // }

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
};

export const getMandatoryHeaders = () => {
  const headers: Record<string, string> = {};

  if (process.env.ASSIGNED_DEVSTACK_LABEL) {
    headers['rzpctx-dev-serve-user'] = process.env.ASSIGNED_DEVSTACK_LABEL;
  } else {
    // throw new Error('Missing `ASSIGNED_DEVSTACK_LABEL`');
  }

  return headers;
};
