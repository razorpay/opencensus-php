import { externalDeps } from '@src/plugins/withDashboardCore/utils/external-deps';
import { envConfig } from '../../env';

export const dashboardBabelConfig = ({
  isNodeApp,
  isDev,
  isShell,
  moduleName,
}: {
  isNodeApp: boolean;
  isDev: boolean;
  isShell: boolean;
  moduleName: string;
}) => ({
  presets: [
    [
      externalDeps['@babel/preset-env'],
      {
        // TODO: Evaluate this and remove manual imports of polyfills from every entry
        // useBuiltIns: 'usage',
        // corejs: 3,
        modules: envConfig.isLegacyMode ? 'auto' : false,
        targets: { esmodules: !envConfig.isLegacyMode },
      },
    ],
    externalDeps['@babel/preset-react'],
    // For typescript support
    [
      externalDeps['@babel/preset-typescript'],
      {
        runtime: 'automatic',
      },
    ],
  ],
  plugins: [
    externalDeps['@babel/plugin-proposal-export-default-from'],
    // Note:
    // 'loose' mode configuration must be the same for
    // * @babel/plugin-proposal-class-properties
    // * @babel/plugin-proposal-private-methods
    // * @babel/plugin-proposal-private-property-in-object
    // (when they are enabled)
    moduleName === 'razorx_dashboard' && [
      externalDeps['@babel/plugin-proposal-decorators'],
      {
        legacy: true,
      },
    ],
    [
      externalDeps['@babel/plugin-proposal-class-properties'],
      {
        loose: true,
      },
    ],
    [
      externalDeps['@babel/plugin-proposal-private-methods'],
      {
        loose: true,
      },
    ],
    [
      externalDeps['@babel/plugin-proposal-private-property-in-object'],
      {
        loose: true,
      },
    ],
    [
      externalDeps['@babel/plugin-transform-runtime'],
      {
        corejs: 3,
        helpers: true,
        regenerator: true,
      },
    ],
    [
      externalDeps['babel-plugin-styled-components'],
      {
        displayName: true,
        pure: false,
        ssr: true,
      },
    ],

    [
      externalDeps['@babel/plugin-transform-spread'],
      {
        loose: true,
      },
    ],
    [externalDeps['babel-plugin-graphql-tag']],

    externalDeps['babel-plugin-react-require'],

    // For ssr apps
    isShell && externalDeps['@loadable/babel-plugin'],

    // For HMR to work
    isDev && !isNodeApp && externalDeps['react-refresh/babel'],

    [
      externalDeps['babel-plugin-transform-react-remove-prop-types'],
      {
        removeImport: true,
      },
    ],
  ].filter(Boolean),
});
