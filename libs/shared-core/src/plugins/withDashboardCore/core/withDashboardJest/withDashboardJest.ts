import { withDashboardBrowserJestConfig } from './configs/jest.browser.config';
import { withDashboardServerJestConfig } from './configs/jest.server.config';
import { WithDashboardJestType } from './types';

export const withDashboardJest: WithDashboardJestType = (configs) => {
  const jestTarget = process.env.JEST_TARGET;

  if (!configs) {
    throw new Error('@withDashboardCore: No configuration found!');
  }

  const allowedKeys = [
    'extendBrowserJestConfig',
    'extendServerJestConfig',
    'browserJestOptions',
    'serverJestOptions',
  ];

  const validateConfigs = (configs: any): void => {
    const keys = Object.keys(configs);

    // Check for unexpected keys
    const invalidKeys = keys.filter((key) => !allowedKeys.includes(key));
    if (invalidKeys.length > 0) {
      throw new Error(
        `@withDashboardCore: Invalid configuration keys detected: ${invalidKeys.join(
          ', ',
        )}. Allowed keys are: ${allowedKeys.join(', ')}`,
      );
    }
  };

  validateConfigs(configs);

  if (
    Boolean(configs.extendBrowserJestConfig) &&
    Boolean(configs.browserJestOptions) &&
    (!Boolean(jestTarget) || jestTarget === 'browser')
  ) {
    return withDashboardBrowserJestConfig(configs);
  }

  if (
    Boolean(configs.extendServerJestConfig) &&
    Boolean(configs.serverJestOptions) &&
    (Boolean(jestTarget) && jestTarget === 'server')
  ) {
    return withDashboardServerJestConfig(configs);
  }
};
