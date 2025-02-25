import path from 'path';
import dotenv from 'dotenv';
import { DASHBOARD_ROOT } from '@src/constants';
import { removeDir } from '@src/scripts';

const authCredsCacheDir = path.resolve(
  DASHBOARD_ROOT,
  'node_modules/.dashboard-core/cache/playwright/.auth',
);

// Global setup for persisting state among pages before going to URL
const globalSetup = () => {
  const TEST_ENV = process.env.TEST_ENV || 'devstack';

  removeDir(authCredsCacheDir);

  if (TEST_ENV) {
    dotenv.config({
      path: path.resolve(
        DASHBOARD_ROOT,
        `libs/shared-core/src/plugins/withDashboardCore/core/withDashboardPlaywright/env/.env.${TEST_ENV}`,
      ),
    });
  }
};

export default globalSetup;
