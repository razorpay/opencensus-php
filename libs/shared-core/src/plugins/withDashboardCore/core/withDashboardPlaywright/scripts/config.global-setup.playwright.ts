import path from 'path';
import dotenv from 'dotenv';
import { DASHBOARD_ROOT } from '@src/constants';

// Global setup for persisting state among pages before going to URL
const globalSetup = () => {
  const TEST_ENV = process.env.TEST_ENV || 'devstack';

  if (TEST_ENV) {
    dotenv.config({
      path: path.resolve(
        DASHBOARD_ROOT,
        `libs/shared-core/src/plugins/withDashboardCore/core/withDashboardPlaywright/env/.env.config.${TEST_ENV}`,
      ),
    });
  }
};

export default globalSetup;
