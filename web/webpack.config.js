const webpack = require('webpack');
const ExtractTextPlugin = require('extract-text-webpack-plugin');
const isProd = require('process').env.NODE_ENV === 'production';

const babelPlugins = [
  ['@babel/plugin-proposal-decorators', { legacy: true }],
  [
    '@babel/plugin-proposal-class-properties',
    {
      loose: true,
    },
  ],
  '@babel/plugin-proposal-export-default-from',
  '@babel/plugin-proposal-do-expressions',
  '@babel/plugin-proposal-function-bind',
  '@babel/plugin-transform-react-display-name',
  '@babel/plugin-transform-react-jsx',
  './babel-plugin-react-html-attrs',
];

if (!isProd) {
  babelPlugins.push(
    '@babel/plugin-transform-react-jsx-self',
    '@babel/plugin-transform-react-jsx-source'
  );
}

const stats = {
  assets: false,
  children: false,
  version: false,
  hash: false,
  timings: false,
  chunks: false,
  chunkModules: false,
};

module.exports = {
  mode: isProd ? 'production' : 'development',

  externals: [].reduce.call(
    (process.env.externals || '').split(/\s+/),
    (prev, next, index, arr) => {
      if (index % 2) {
        prev[next.split('/')[0]] = arr[index - 1];
      }
      return prev;
    },
    {}
  ),

  entry: {
    pokedex: './js/pokedex/index.js',
    merchantLA: './js/merchantLA/index.js',
    merchant: './js/merchant/index.js',
    razorx: './js/razorx/index.js',
  },

  output: {
    path: __dirname + '/../public/dist',
    filename: '[name].js',
  },

  resolve: {
    modules: ['js', 'node_modules'],
  },

  stats,

  module: {
    rules: [
      {
        test: /\.js$/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader',
          options: {
            babelrc: false,
            presets: [
              [
                '@babel/preset-env',
                {
                  loose: true,
                  modules: false,
                  useBuiltIns: 'entry',
                  corejs: 3,
                  targets: {
                    browsers: ['> .25%', 'ie >= 11'],
                  },
                },
              ],
              '@babel/preset-react',
            ],
            plugins: babelPlugins,
          },
        },
      },
      {
        test: /\.styl$/,
        use: ExtractTextPlugin.extract({
          use: [
            {
              loader: 'css-loader',
            },
            {
              loader: 'stylus-loader',
            },
          ],
        }),
      },
    ],
  },

  devtool: isProd ? false : false,
  devServer: {
    stats,
  },

  plugins: [
    new ExtractTextPlugin({
      filename: '[name].css',
    }),
  ],
};
