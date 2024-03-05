process.env.STAGE = 'development';
const path = require('path');
const babelConfig = require('../.babelrc.js');
const universeWebpackClientConfig = require(path.resolve(__dirname, '../webpack.config.js'))({
  babelConfig: babelConfig,
});
const webpackClientConfig = require(path.resolve(__dirname, `../webpack.config.js`)).browserConfig({
  config: {
    ...universeWebpackClientConfig,
    experiments: { backCompat: false },
  },
  isStoryBook: true,
  project: 'merchant',
});
webpackClientConfig.module.rules[0].use.push({
  loader: require.resolve('react-docgen-typescript-loader'),
  options: {
    // Provide the path to your tsconfig.json so that your stories can
    // display types from outside each individual story.
    tsconfigPath: path.resolve(__dirname, '../tsconfig.json'),
  },
});
webpackClientConfig.module.rules.push({
  test: /.css$/,
  use: [
    {
      loader: 'style-loader', // creates style nodes from JS strings
    },
    {
      loader: 'css-loader',
      options: {
        url: false,
        importLoaders: 2,
      }, // translates CSS into CommonJS
    },
  ],
});

module.exports = {
  stories: ['../js/**/*.stories.[tj]sx'],
  addons: ['@storybook/addon-links', '@storybook/addon-knobs', '@storybook/addon-essentials'],
  webpackFinal: async (config) => {
    // do mutation to the config
    return {
      ...config,
      module: { ...config.module, rules: webpackClientConfig.module.rules },
      resolve: webpackClientConfig.resolve,
    };
  },
};
