import path from 'path';
import { getBaseUrl, getProjects, getReporter, getMandatoryHeaders } from './utils';
import { PlaywrightConfigType, WithDashboardJestType } from './types';
import { DASHBOARD_ROOT } from '@src/constants';
import os from 'os';

export const withDashboardPlaywright: WithDashboardJestType = (args) => {
  const isCI = process.env.CI === 'true';
  const { playwrightOptions, extendPlaywrightConfig } = args;
  const targetProject = playwrightOptions.moduleName;
  const baseProjectConfigs = getProjects({ projectType: targetProject });
  const globalSetup =
    // @ts-ignore
    targetProject === 'Login'
      ? path.resolve(DASHBOARD_ROOT, 'libs/shared-core/dist/global-setup.playwright.cjs')
      : undefined;

  const config: PlaywrightConfigType = {
    testMatch: ['**/?(*.)+(spec).[jt]s?(x)'],
    globalSetup,
    retries: isCI ? 2 : 0,
    timeout: 6 * 60 * 1000,
    // @ts-ignore
    reporter: getReporter(),
    fullyParallel: true,
    forbidOnly: !!isCI,
    // This includes retries also in the maxFailures count (45/3 = 15 unique failures - worst case scenario)
    // maxFailures: isCI ? 45 : undefined,
    expect: {
      timeout: 30 * 1000,
    },
    outputDir: path.resolve(process.cwd(), '.playwright-analysis/run-files'),
    use: {
      bypassCSP: !!isCI,
      launchOptions: {
        args: ['--disable-web-security'],
      },
      baseURL: getBaseUrl(),
      headless: !!isCI,
      screenshot: 'only-on-failure',
      trace: 'retain-on-failure',
      video: 'on-first-retry',
      actionTimeout: 30 * 1000,
      permissions: ['clipboard-read', 'clipboard-write', 'accessibility-events'],
      contextOptions: {
        strictSelectors: true,
        extraHTTPHeaders: getMandatoryHeaders(),
      },
    },
    projects: baseProjectConfigs,
  };

  const finalConfig = extendPlaywrightConfig?.(config as unknown as PlaywrightConfigType);

  if (!finalConfig) {
    throw new Error(
      '[@libs/shared-core] withDashboardPlaywright: extendPlaywrightConfig should return a valid PlaywrightConfigType',
    );
  }

  if (!os.cpus().length) {
    throw new Error(
      '[@libs/shared-core] withDashboardPlaywright: Could not determine number of CPUs',
    );
  }

  finalConfig.workers = os.cpus().length / 2;

  return finalConfig;
};
