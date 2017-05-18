'use strict';

const path = require('path');
const webpack = require('webpack');
const ExtractTextPlugin = require('extract-text-webpack-plugin');
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
    extensions: ['.js', '.jsx', '.styl'],
  },
  module: {},
  externals: {
    jquery: 'jQuery',
  },

  stats: {
    children: false,
  },
};

// ------------------------------------
// Entry Points
// ------------------------------------
webpackConfig.entry = {
  // merchant: './merchant',
  merchant: './merchant/index_new',
  // admin: './admin',
};

// ------------------------------------
// Bundle Output
// ------------------------------------
webpackConfig.output = {
  path: path.resolve(__dirname, 'public/dist'),
  filename: '[name]_react.js',
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
];

// ------------------------------------
// Plugins
// ------------------------------------
webpackConfig.plugins = [
  new CaseSensitivePathsPlugin(),

  new webpack.ProvidePlugin({
    React: 'react',
  }),

  new ExtractTextPlugin({
    filename: '[name].css',
  }),
];

module.exports = webpackConfig;
