import { DASHBOARD_ROOT } from '@src/constants';
import path from 'path';

export const PLAYWRIGHT_CACHE_DIR = path.resolve(
  DASHBOARD_ROOT,
  'node_modules/.dashboard-core/cache/playwright',
);

export const PLAYWRIGHT_SETUP_CACHE_DIR = path.resolve(PLAYWRIGHT_CACHE_DIR, '.setup');

export const DEVSTACK_FILE_FOR_E2E_RUN = path.resolve(PLAYWRIGHT_SETUP_CACHE_DIR, 'devstack.json');

export const DEVSTACK_OVERRIDE_FILE_FOR_E2E_RUN = path.resolve(
  DASHBOARD_ROOT,
  'libs/shared-core/src/plugins/withDashboardCore/core/withDashboardPlaywright/config/devstack.json',
);
