'use strict'

const path = require('path')
const webpack = require('webpack')
const CaseSensitivePathsPlugin = require('case-sensitive-paths-webpack-plugin');

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
    extensions: ['.js', '.jsx', '.styl']
  },
  module: {},
  externals: {
    'jquery': 'jQuery'
  }
}

// ------------------------------------
// Entry Points
// ------------------------------------
webpackConfig.entry = {
  merchant: './merchant'
}

// ------------------------------------
// Bundle Output
// ------------------------------------
webpackConfig.output = {
  path: './public/react/dist',
  filename: '[name]_react.js'
}

// ------------------------------------
// Loaders
// ------------------------------------
webpackConfig.module.loaders = [
  {
    test: /\.(js|jsx)$/,
    include: path.resolve(__dirname, 'public/react'),
    loader: 'babel-loader',
    query: {
      cacheDirectory: true,
      plugins: [
        'react-html-attrs',
        'transform-runtime',
        'transform-decorators-legacy'
      ],
      presets: ['es2015', 'react', 'stage-0']
    }
  },
  {
    test: /\.styl$/,
    loader: 'style-loader!css-loader?modules&localIdentName=[local]!stylus-loader?paths=/public/react'
  }
]


// ------------------------------------
// Plugins
// ------------------------------------
webpackConfig.plugins = [
  new CaseSensitivePathsPlugin(),

  /* https://github.com/webpack/webpack/issues/3128 */
  new webpack.IgnorePlugin(/(locale)/, /node_modules.+(momentjs)/)
]

module.exports = webpackConfig
