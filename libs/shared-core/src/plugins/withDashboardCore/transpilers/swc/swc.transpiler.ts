import { externalDeps } from '@src/plugins/withDashboardCore/utils/external-deps';
import { envConfig } from '../../env';

export const dashboardSwcConfig = ({
  isNodeApp,
  isDev,
  isShell,
}: {
  isNodeApp: boolean;
  isDev: boolean;
  isShell: boolean;
}) => {
  return {
    // Equivalent to @babel/preset-react
    env: {
      // es2017
      targets: ['defaults', 'not IE 11', 'not op_mini all'], // target browser
      mode: 'usage', // usage, entry, false
      debug: false,
      dynamicImport: true,
      loose: true,
      include: ['transform-regenerator',  'transform-spread'], // can be a core-js module (es.math.sign) or an SWC pass (transform-spread).
      /**
       * The option has an effect when used alongside mode: "usage" or mode: "entry".
       * It is recommended to specify the minor version (E.g. "3.22") otherwise "3"
       * will be interpreted as "3.0" which may not include polyfills for the latest features.
       */
      coreJs: '3.6.5',
      shippedProposals: false,
      forceAllTransform: false, //  Enable all possible transforms.
      bugfixes: false,
    },
    minify: !isDev, // Enable minification
    jsc: {
      parser: {
        syntax: 'typescript', // typescript | "ecmascript"
        target: 'es2017',
        // These assumptions (opens in a new tab) are made in loose mode by default,
        // you might get unexpected reault if your code doesn't meet these assumptions.
        // Ref: https://babeljs.io/docs/assumptions
        loose: true, // similar to babel-preset-env
        tsx: true,
        jsx: true,
        privateMethod: true,
        classPrivateProperty: true,
        decoratorsBeforeExport: true,
        exportDefaultFrom: true,
        decorators: true,
        dynamicImport: true,
      },
      transform: {
        decoratorMetadata: true,
        legacyDecorator: true,
        react: {
          runtime: 'automatic', // classic | automatic
          useBuiltins: true, //  Use Object.assign() instead of _extends. Defaults to false.
        },
        // refresh: true, // Enable React Fast Refresh
      },
      preserveAllComments: false,
      // Minify options, refer: https://swc.rs/docs/configuration/minification
      minify: {
        compress: {
          unused: true,
        },
        mangle: true,
      },
      experimental: {
        keepImportAttributes: false, // Preserve import attributes. This is experimental because import attributes are not covered by EcmaScript specifications yet.
      },
      output: {
        charset: 'utf8', // ascii | utf8
      },
    },
  };
};
