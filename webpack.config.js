'use strict'

const path = require('path')
const webpack = require('webpack')

const webpackConfig = {
  context: process.cwd() + '/public/react',
  resolve: {
    alias: {
      moment: 'moment/min/moment.min.js'
    },
    modules: [
      path.resolve('node_modules'),
      path.resolve('public/react'),
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
webpackConfig.plugins = []

module.exports = webpackConfig
