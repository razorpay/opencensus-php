'use strict'

const path = require('path')
const webpack = require('webpack')

const webpackConfig = {
  context: process.cwd() + '/public/react',
  resolve: {
    root: [
      path.resolve('node_modules'),
      path.resolve('public/react'),
    ],
    extensions: ['', '.js', '.jsx', '.styl']
  },
  module: {}
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
  filename: '[name].js'
}

// ------------------------------------
// Loaders
// ------------------------------------
webpackConfig.module.loaders = [
  {
    test: /\.(js|jsx)$/,
    include: path.resolve(__dirname, 'public/react'),
    loader: 'babel',
    query: {
      cacheDirectory: true,
      plugins: ['transform-runtime', 'transform-decorators-legacy'],
      presets: ['es2015', 'react', 'stage-0']
    }
  },
  {
    test: /\.styl$/,
    loader: 'style!css?modules&localIdentName=[local]!stylus?paths=/public/react'
  }
]


// ------------------------------------
// Plugins
// ------------------------------------
webpackConfig.plugins = []

module.exports = webpackConfig
