'use strict';

const path = require('path');
const webpack = require('webpack');
const CaseSensitivePathsPlugin = require('case-sensitive-paths-webpack-plugin');
const BabiliPlugin = require('babili-webpack-plugin');

const commonConfig = {
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
// Bundle Output
// ------------------------------------
commonConfig.output = {
  publicPath: '/dist/',
  path: path.resolve(__dirname, 'public/dist'),
};

// ------------------------------------
// Plugins
// ------------------------------------
commonConfig.plugins = [
  new CaseSensitivePathsPlugin(),

  new webpack.ProvidePlugin({
    React: 'react',
    $: 'jquery',
  }),
];

module.exports = env => {
  const isProd = env === 'production' ? true : false;
  commonConfig.output.filename = isProd ? '[name]_[chunkhash].js' : '[name].js';

  // ------------------------------------
  // Loaders
  // ------------------------------------
  commonConfig.module.rules = [
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
            presets: [
              ['es2015', { loose: true, modules: false }],
              'react',
              'stage-0',
            ],
          },
        },
      ],
    },
    {
      test: /\.(woff|woff2|eot|ttf)$/,
      use: [
        {
          loader: 'file-loader',
          options: {
            publicPath: process.env.WERCKER ? '/dashboard/dist/' : '/dist/',
          },
        },
      ],
    },
    {
      test: /\.(png|svg)$/,
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

  // Production specific plugins
  if (isProd) {
    commonConfig.plugins.push(
      new webpack.DefinePlugin({
        'process.env': {
          NODE_ENV: JSON.stringify('production'),
        },
      }),
      new BabiliPlugin({
        mangle: { topLevel: true },
      }),
      new webpack.optimize.UglifyJsPlugin({
        compress: {
          warnings: false,
        },
        output: {
          comments: false,
        },
      })
    );
  }

  return [
    require('./webpack.merchant.js')(commonConfig, isProd)
  ];
};
