import { DASHBOARD_FEDERATED_MODULES, DASHBOARD_ROOT } from '@src/constants';
import { getAliasedDashboardModules } from '@src/plugins/withDashboardCore/utils/getAliasedDashboardModules';
import path from 'path';
import fs from 'fs';

const isDirectory = (filePath: string) => {
  try {
    const stat = fs.statSync(filePath);
    return stat.isDirectory();
  } catch (error: any) {
    if (error?.code === 'ENOENT') {
      // Path does not exist
      return 'NON_EXISTENT';
    }
    throw error;
  }
};

const createMappings = (aliases: Record<string, string>, rootDir: string) => {
  return Object.entries(aliases).reduce((acc, [key, value]) => {
    const isDir = isDirectory(path.resolve(value));
    const aliasKey = isDir ? `^${key.replace('/*', '')}(/.*)?$` : `^${key}`;
    const isFederatedModule = key.includes('@federated/');
    const dirValue = value.replace('/*', '');
    const rootDirRelative = path.resolve(
      rootDir,
      isFederatedModule ? path.resolve(DASHBOARD_ROOT, dirValue) : dirValue,
    );

    acc[aliasKey] = isDir ? `${rootDirRelative}$1` : rootDirRelative;

    return acc;
  }, {} as Record<string, string>);
};

export const moduleNameMapGenerator = (
  moduleName: DASHBOARD_FEDERATED_MODULES,
  rootDir: string,
) => {
  try {
    // Step 1: Load aliases from getAliasedDashboardModules
    const dashboardAliases = getAliasedDashboardModules({
      moduleName,
      nodeModuleResolutionMode: 'path',
      skipBypassForBrowsers: true,
    });

    // Step 2: Load tsconfig.base.json and extract @federated/* paths
    const tsConfigPath = path.resolve(DASHBOARD_ROOT, './tsconfig.base.json');
    const tsConfig = require(tsConfigPath);
    const tsConfigPaths = tsConfig.compilerOptions?.paths || {};

    const federatedPaths: Record<string, string> = Object.fromEntries(
      Object.entries(tsConfigPaths)
        .filter(([key]) => key.startsWith('@federated/')) // Only @federated/* keys
        .map(([key, values]) => [key, Array.isArray(values) ? values[0] : values]), // Extract first array value
    );

    const dashboardMappings = createMappings(dashboardAliases, rootDir);
    const federatedMappings = createMappings(federatedPaths, rootDir);

    const combinedMappings = { ...dashboardMappings, ...federatedMappings };
    
    return combinedMappings;
  } catch (err) {
    console.error(err);
    return {};
  }
};
