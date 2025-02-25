const { withNx } = require('@nx/rollup/with-nx');
const resolve = require('@rollup/plugin-node-resolve');
const commonjs = require('@rollup/plugin-commonjs');
const json = require('@rollup/plugin-json');
const { peerDependencies } = require('./package.json');

module.exports = withNx(
  {
    main: './packages/index.ts',
    outputPath: './dist',
    tsConfig: './tsconfig.lib.json',
    compiler: 'swc',
    allowJs: true,
    format: ['cjs'],
    assets: [],
    external: Object.keys(peerDependencies),
    additionalEntryPoints: ['./packages/jest.ts', './packages/playwright.ts'],
  },
  {
    plugins: [
      resolve({
        preferBuiltins: true,
        preserveSymlinks: true,
      }),
      commonjs(),
      json(),
    ],
  },
);
