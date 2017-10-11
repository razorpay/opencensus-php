/*
  All Common configurations in admin and merchant build config
*/
const webpack = require('webpack');
const path = require('path');

const isProd = require('process').env.NODE_ENV === 'production';

module.exports = {
  dependencies: ['vendor'],
  resolve: {
    modules: ['node_modules', 'web/js'],
  },

  resolveLoader: {
    alias: {
      'dot-loader': __dirname + '/../dot-loader.js',
    },
  },

  stats: {
    assets: false,
    children: false,
    version: false,
    hash: false,
    timings: false,
    chunks: false,
    chunkModules: false,
  },

  module: {
    rules: [
      {
        test: /\.jst$/,
        loader: 'dot-loader',
      },
      {
        test: /\.js$/,
        exclude: /^node_modules/,
        use: {
          loader: 'babel-loader',
          options: {
            presets: ['env', 'react', 'stage-0'],
            plugins: ['transform-decorators-legacy', 'react-html-attrs'],
          },
        },
      },
    ],
  },
};
