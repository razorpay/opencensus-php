import { withDashboardBrowserWebpackConfig } from './configs/browser.webpack.config';
import { withDashboardServerWebpackConfig } from './configs/server.webpack.config';
import { WithDashboardWebpackConfigs, WithDashboardWebpackType } from './types';
import path from 'path';

export const withDashboardWebpack: WithDashboardWebpackType = (configs) => {
  const targetBuild = process.env.TARGET_BUILD;

  if (!configs) {
    throw new Error('@withDashboardCore: No configuration found!');
  }

  const allowedKeys = [
    'extendBrowserWebpackConfig',
    'extendServerWebpackConfig',
    'serverBundlerOptions',
    'browserBundlerOptions',
  ];

  const validateConfigs = (configs: WithDashboardWebpackConfigs): void => {
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

  const finalConfigs: ReturnType<WithDashboardWebpackType> = {};

  const isBrowserConfigPresent = Boolean(
    configs.extendBrowserWebpackConfig && configs.browserBundlerOptions,
  );
  const isServerConfigPresent = Boolean(
    configs.extendServerWebpackConfig && configs.serverBundlerOptions,
  );

  if (targetBuild) {
    if (targetBuild === 'browser' && isBrowserConfigPresent) {
      finalConfigs.browserConfig = withDashboardBrowserWebpackConfig(configs);
    } else if (targetBuild === 'server' && isServerConfigPresent) {
      finalConfigs.serverConfig = withDashboardServerWebpackConfig(configs);
    }
  } else {
    if (isBrowserConfigPresent)
      finalConfigs.browserConfig = withDashboardBrowserWebpackConfig(configs);

    if (isServerConfigPresent)
      finalConfigs.serverConfig = withDashboardServerWebpackConfig(configs);
  }

  console.log(finalConfigs);

  return finalConfigs;
};
