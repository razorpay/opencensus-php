const { withNx } = require('@nx/rollup/with-nx');
const url = require('@rollup/plugin-url');
const svg = require('@svgr/rollup');
const resolve = require('@rollup/plugin-node-resolve');
const commonjs = require('@rollup/plugin-commonjs');
const alias = require('@rollup/plugin-alias');
const path = require('path');

module.exports = withNx(
  {
    main: './src/common/index.ts',
    outputPath: './dist',
    tsConfig: './tsconfig.lib.json',
    compiler: 'babel',
    external: ['react', 'react-dom', 'react/jsx-runtime'],
    format: ['esm', "cjs"],
    assets: [{ input: '.', output: '.', glob: 'README.md' }],
  },
  {
    plugins: [
      svg({
        svgo: false,
        titleProp: true,
        ref: true,
      }),
      url({
        limit: 10000, // 10kB
      }),
      resolve(),
      commonjs(),
      alias({
        entries: [
          { find: '@libs/shared-types', replacement: path.resolve(__dirname, '../shared-types/src/common/index.ts') },
        ],
      })
    ],
  },
);
