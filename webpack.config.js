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
            'transform-react-jsx-img-import',
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
    name: 'vendor',
    minChunks: function(module) {
      return module.context && module.context.indexOf('node_modules') !== -1;
    },
  }),

  new webpack.optimize.CommonsChunkPlugin({
    name: 'manifest', //But since there are no more common modules between them we end up with just the runtime code included in the manifest file
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
