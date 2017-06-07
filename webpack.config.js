'use strict';

const path = require('path');
const webpack = require('webpack');
const ExtractTextPlugin = require('extract-text-webpack-plugin');
const HtmlWebpackPlugin = require('html-webpack-plugin');

const CaseSensitivePathsPlugin = require('case-sensitive-paths-webpack-plugin');
const bootstrap = require('bootstrap-styl');

const webpackConfig = {
  context: process.cwd() + '/public/react',
  resolve: {
    alias: {
      moment: 'moment/min/moment.min.js',
      react: path.resolve(__dirname, 'node_modules/react'),
      'react-dom': path.resolve(__dirname, 'node_modules/react-dom'),
    },
    modules: [
      path.resolve(__dirname, 'node_modules'),
      'web_modules',
      path.resolve(__dirname, 'public/react'),
    ],
    extensions: ['.js', '.jsx', '.styl', '.jst'],
  },
  module: {},
  stats: {
    children: false,
  },
};

// ------------------------------------
// Entry Points
// ------------------------------------
webpackConfig.entry = {
  vendor: [
    'jquery',
    'chart.js',
    'classnames',
    'moment',
    'react',
    'react-addons-shallow-compare',
    'react-async-button',
    'react-chartjs-2',
    'react-dates',
    'react-dom',
    'react-modal',
    'react-power-select',
    'react-redux',
    'react-router-dom',
    'react-simple-dropdown',
    'react-tabs',
    'react-tether',
    'react-time',
    'redux',
    'redux-form',
  ],
  merchant: './merchant/index',
};

// ------------------------------------
// Bundle Output
// ------------------------------------
webpackConfig.output = {
  publicPath: '/dist/',
  path: path.resolve(__dirname, 'public/dist'),
  filename: '[name]_[chunkhash].js',
};

// ------------------------------------
// Loaders
// ------------------------------------
webpackConfig.module.rules = [
  {
    test: /\.(js|jsx)$/,
    include: path.resolve(__dirname, 'public/react'),
    use: [
      {
        loader: 'babel-loader',
        options: {
          cacheDirectory: true,
          plugins: [
            'react-html-attrs',
            'transform-runtime',
            'transform-decorators-legacy',
          ],
          presets: ['es2015', 'react', 'stage-0'],
        },
      },
    ],
  },
  {
    test: /\.styl$/,
    // exclude: path.resolve(__dirname, 'node_modules'),
    use: ExtractTextPlugin.extract({
      fallback: 'style-loader',
      use: [
        {
          loader: 'css-loader',
          options: {
            minimize: true,
          },
        },
        {
          loader: 'postcss-loader',
          options: {
            plugins: loader => [require('autoprefixer')()],
          },
        },
        {
          loader: 'stylus-loader',
          options: {
            use: bootstrap(),
            paths: 'node_modules/bootstrap-styl/bootstrap',
          },
        },
      ],
    }),
  },

  {
    test: /\.(png|woff|woff2|eot|ttf|svg)$/,
    use: [
      {
        loader: 'file-loader',
      },
    ],
  },

  {
    test: /\.jst$/,
    use: [
      {
        loader: 'dot-tpl-loader',
      },
    ],
  },
];

// ------------------------------------
// Plugins
// ------------------------------------
webpackConfig.plugins = [
  new CaseSensitivePathsPlugin(),

  new webpack.optimize.CommonsChunkPlugin({
    names: ['vendor', 'manifest'], // Specify the common bundle's name.
  }),

  new webpack.ProvidePlugin({
    React: 'react',
    $: 'jquery',
  }),

  new ExtractTextPlugin({
    filename: '[name]_[chunkhash].css',
  }),

  new HtmlWebpackPlugin({
    template: path.resolve(
      __dirname + '/resources/views/merchant/getIndex.blade.php'
    ),
    filename: path.resolve(
      __dirname + '/resources/views/merchant/tmpgetIndex.blade.php'
    ),
    inject: false,
  }),
];

module.exports = webpackConfig;
