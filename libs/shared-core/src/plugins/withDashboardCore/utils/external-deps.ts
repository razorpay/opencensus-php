import path from 'path';
import { DASHBOARD_ROOT } from '../../../';

/**
 * Resolves a module from the root `node_modules` directory in `DASHBOARD_ROOT`.
 *
 * Supports two resolution types:
 * - `'node'`: [Default] Uses Node.js `require.resolve` to resolve the module based on Node.js module resolution rules.
 * - `'path'`: Resolves the path statically using `path.resolve` (does not validate existence).
 *
 * @param module - The name or relative path of the module to resolve (e.g., '@babel/preset-env', 'custom-folder/my-file.js').
 * @param options - Options to configure the resolution behavior.
 * @param options.resolutionType - Determines the resolution type ('node' or 'path').
 * @returns The absolute path to the resolved module.
 * @throws Error if the module cannot be resolved.
 */
export const resolveFromRootNodeModules = (
  module: string,
  {
    resolutionType,
  }: {
    resolutionType: 'node' | 'path';
  },
): string | undefined => {
  try {
    if (resolutionType == 'path') {
      return path.resolve(DASHBOARD_ROOT, `node_modules/${module}`);
    } else {
      return require.resolve(module, {
        paths: [path.resolve(DASHBOARD_ROOT, 'node_modules')],
      });
    }
  } catch (err) {
    console.warn(
      `Module "${module}" could not be resolved from root node_modules. Ensure it is installed.`,
    );
  }
};

const externalDepsResolvedFromRootNodeModules = [ 
  // Plugins
  'mini-css-extract-plugin',
  '@sentry/webpack-plugin',
  '@pmmmwh/react-refresh-webpack-plugin',
  'node-polyfill-webpack-plugin',
  '@loadable/webpack-plugin',
  'terser-webpack-plugin',
  'compression-webpack-plugin',
  'webpack-bundle-analyzer',
  'error-overlay-webpack-plugin',
  'css-minimizer-webpack-plugin',
  'webpackbar',
  'nodemon-webpack-plugin',

  // Jest
  'jest-canvas-mock',
  'jest-location-mock',
  'jest-sonar',
  'jest-html-reporters',
  'jest-environment-jsdom',
  'whatwg-fetch',
  'web-streams-polyfill',

  // Loaders
  'babel-jest',
  'babel-loader',
  'graphql-tag/loader',
  'style-loader',
  'css-loader',
  'stylus-loader',
  '@babel/preset-env',
  '@babel/preset-react',
  '@babel/preset-typescript',
  '@babel/plugin-proposal-export-default-from',
  '@babel/plugin-proposal-decorators',
  '@babel/plugin-proposal-class-properties',
  '@babel/plugin-proposal-private-methods',
  '@babel/plugin-proposal-private-property-in-object',
  '@babel/plugin-transform-runtime',
  'babel-plugin-styled-components',
  '@babel/plugin-proposal-do-expressions',
  '@babel/plugin-proposal-function-bind',
  '@babel/plugin-transform-spread',
  'babel-plugin-graphql-tag',
  '@loadable/babel-plugin',
  'react-refresh/babel',
  'babel-plugin-transform-react-remove-prop-types',
  'webfonts-loader',
  'node-loader',
  'babel-plugin-react-require',
  'thread-loader',
] as const;

export const externalDeps = externalDepsResolvedFromRootNodeModules.reduce(
  (prev, curr) => ({
    ...prev,
    [curr]: resolveFromRootNodeModules(curr, {
      resolutionType: 'node',
    }),
  }),
  {},
) as unknown as Record<(typeof externalDepsResolvedFromRootNodeModules)[number], string>;
