import path from 'path';
import { DASHBOARD_FEDERATED_MODULES, DASHBOARD_ROOT } from '../../../constants';
import { resolveFromRootNodeModules } from '@src/plugins/withDashboardCore/utils/external-deps';

const getWebNexusAliases = () => {
  // prettier-ignore
  return {
    /**
     * @deprecated These imports will not be allowed anymore. Handled by eslint, enforced via Validate Workflow
     * @context Why is it added then?
     * When importing any `@lib/web-nexus/*`, internally it may be using older version of
     * imports at some nested level. This is to make sure its resolution is properly handled by webpack.
     */
    assets: path.resolve(DASHBOARD_ROOT, 'web/css/assets'),
    common: path.resolve(DASHBOARD_ROOT, 'web/js/common'),
    merchant: path.resolve(DASHBOARD_ROOT, 'web/js/merchant'),
    merchant_common: path.resolve(DASHBOARD_ROOT, 'web/js/merchant_common'),
    merchantLA: path.resolve(DASHBOARD_ROOT, 'web/js/merchantLA'),
    newAuth: path.resolve(DASHBOARD_ROOT, 'web/js/newAuth'),
    razorx: path.resolve(DASHBOARD_ROOT, 'web/js/razorx'),
    icons: path.resolve(DASHBOARD_ROOT, 'web/icons'),

    /**
     * Web Nexus - Dashboards
     * Do not add it in any libs intended for connected dashboard. 
     */
    '@dashboards/la': path.resolve(DASHBOARD_ROOT, 'web/js/merchantLA'),
    '@dashboards/payments': path.resolve(DASHBOARD_ROOT, 'web/js/merchant'),
    '@dashboards/pokedex': path.resolve(DASHBOARD_ROOT, 'web/js/pokedex'),
    '@dashboards/razorx': path.resolve(DASHBOARD_ROOT, 'web/js/razorx'),
    '@dashboards/tnc': path.resolve(DASHBOARD_ROOT, 'web/js/merchantTnc'),
    '@dashboards/newAuth': path.resolve(DASHBOARD_ROOT, 'web/js/newAuth'),

    /**
     * Web Nexus [Overridden Exceptions] - Libs
     */
    '@libs/web-nexus/common': path.resolve(DASHBOARD_ROOT, 'web/js/common'),
    '@libs/web-nexus/merchant': path.resolve(DASHBOARD_ROOT, 'web/js/merchant_common'),
  };
};

/**
 * Do not add it in any libs intended for connected dashboard.
 */
const getAliasedSharedLibs = () => {
  // prettier-ignore
  return {    
    // Main Libs For The Connected Dashboard (Nesting Restricted, Keep flat structure always)
    '@libs/shared-types': path.resolve(DASHBOARD_ROOT, 'libs/shared-types/src/common/index.ts'),
    '@libs/shared-types/la': path.resolve(DASHBOARD_ROOT, 'libs/shared-types/src/dashboards/la/index.ts'),
    '@libs/shared-types/payments': path.resolve(DASHBOARD_ROOT, 'libs/shared-types/src/dashboards/payments/index.ts'),
    '@libs/shared-ui': path.resolve(DASHBOARD_ROOT, 'libs/shared-ui/src/index.ts'),
    '@libs/shared-utils': path.resolve(DASHBOARD_ROOT, 'libs/shared-utils/src/common/index.ts'),
  };
};

const getTargetAppAlias = () => {
  return {
    // Microapps Root Alias
    '@apps': path.resolve(DASHBOARD_ROOT, 'apps'),
  };
};

const getBypassedModulesForBrowserApp = () => {
  return {
    fs: false,
  };
};

/**
 * @context When importing a runtime lib, it may import some external vendors to work properly
 * It gets resolved for stateless libs, separate instances are created in that scenario.
 * But for stateful libs which depends on context, etc we need to make sure its always singleton.
 * Otherwise it will throw errors.
 *
 * Common Errors You'll Face Without This:
 * @example`BladeProvider not found`
 *
 * Also keeping one source of truth for will help in maintaining versions that change pretty often,
 * like @razorpay/*
 */
const enforcedAliasedNodeModules = (resolutionType: 'node' | 'path') => {
  const runtimeDeps = [
    '@razorpay/blade',
    '@razorpay/blade-old',
    '@razorpay/blade-old-for-new-auth',
    '@razorpay/i18nify-js',
    '@razorpay/i18nify-react',
    '@razorpay/universe-cli',
    '@razorpay/universe-utils',
    '@tanstack/query-core',
    '@tanstack/react-query',
    'history',
    'react',
    'react-dom',
    'react-redux',
    'react-router-dom',
    'redux',
    'redux-form',
    'styled-components',
    'xlsx',
    'zustand',
  ];

  return runtimeDeps.reduce((prev, curr) => {
    const resolvedPath = resolveFromRootNodeModules(curr, {
      resolutionType,
    });
    if (resolvedPath) {
      // @ts-ignore
      prev[curr] = resolvedPath;
    }
    return prev;
  }, {});
};

/**
 * Single source of truth for all aliases for all modules in dashboard repo.
 * Should be in sync with `<workspaceRoot>/tsconfig.base.json`
 */
export const getAliasedDashboardModules = ({
  moduleName,
  nodeModuleResolutionMode = 'path',
  skipBypassForBrowsers = false,
}: {
  moduleName: DASHBOARD_FEDERATED_MODULES;
  nodeModuleResolutionMode?: 'path' | 'node';
  skipBypassForBrowsers?: boolean;
}) => {
  /**
   * Shell should have no coupling with any other application. It will effect ci setup.
   */
  const isShell = [
    DASHBOARD_FEDERATED_MODULES.SHELL,
    DASHBOARD_FEDERATED_MODULES.SHELL_SERVER,
    DASHBOARD_FEDERATED_MODULES.SHELL_SERVER_STREAM,
  ].includes(moduleName);

  const isShellServer = moduleName === DASHBOARD_FEDERATED_MODULES.SHELL_SERVER;

  const baseAliases =
    isShellServer || skipBypassForBrowsers ? {} : getBypassedModulesForBrowserApp();
  const conditionalAliases = isShell ? {} : getWebNexusAliases();
  const sharedLibAliases = getAliasedSharedLibs();
  const targetAppAlias = getTargetAppAlias(); // TODO: Complete this implementation
  const enforcedNodeModules = enforcedAliasedNodeModules(nodeModuleResolutionMode);
  return {
    ...baseAliases,
    ...conditionalAliases,
    ...sharedLibAliases,
    ...targetAppAlias,
    ...enforcedNodeModules,
  };
};
