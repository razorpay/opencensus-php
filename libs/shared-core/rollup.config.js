const path = require('path');
const alias = require('@rollup/plugin-alias'); // Import alias plugin
const commonjs = require('@rollup/plugin-commonjs');
const json = require('@rollup/plugin-json');
const resolve = require('@rollup/plugin-node-resolve');
const { withNx } = require('@nx/rollup/with-nx');

const externalModules = [];

module.exports = withNx(
  {
    main: './src/index.ts',
    outputPath: './dist',
    tsConfig: './tsconfig.lib.json',
    compiler: 'swc',
    format: ['cjs'],
    assets: [{ input: '.', output: '.', glob: '*.md' }],
    additionalEntryPoints: [
      './src/dashboard-cli/dashboard-cli.ts',
      './src/dashboard-cli/core/scaffold-app/scaffold-app.ts',
      './src/plugins/withDashboardCore/core/withDashboardJest/transformers/babelTransformer.jest.ts',
      './src/plugins/withDashboardCore/core/withDashboardJest/transformers/fileTransformer.jest.ts',
      './src/plugins/withDashboardCore/core/withDashboardPlaywright/scripts/addProdCommit.playwright.ts',
      './src/plugins/withDashboardCore/core/withDashboardPlaywright/scripts/initializeE2EInfra.playwright.ts',
      './src/plugins/withDashboardCore/core/withDashboardPlaywright/scripts/auth.global-setup.playwright.ts',
      './src/plugins/withDashboardCore/core/withDashboardPlaywright/scripts/config.global-setup.playwright.ts',
    ],
  },
  {
    plugins: [
      alias({
        entries: [
          { find: '@src', replacement: path.resolve(__dirname, 'src') }, // Add alias for `@src`
        ],
      }),
      resolve({
        preferBuiltins: true,
      }),
      commonjs(),
      json(),
    ],
    external: (id) => {
      // Always bundle the specified modules
      if (externalModules.some((module) => id.includes(module))) {
        return false; // Include these modules in the bundle
      }

      // Exclude all other `node_modules` dependencies
      return /node_modules/.test(id);
    },
  },
);
