'use strict';

const path = require('path');
const webpack = require('webpack');
const ExtractTextPlugin = require('extract-text-webpack-plugin');
const HtmlWebpackPlugin = require('html-webpack-plugin');
const bootstrap = require('bootstrap-styl');
const webpackMerge = require('webpack-merge');

module.exports = (commonConfig, isProd) => {
  const merchantConfig = {
    entry: {
      merchant: './merchant/index',
    },
    module: {
      rules: [
        {
          test: /\.styl$/,
          loader: isProd
            ? ExtractTextPlugin.extract({
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
              })
            : [
                {
                  loader: 'style-loader',
                },
                {
                  loader: 'css-loader',
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
        },
      ],
    },
    plugins: [
      new webpack.optimize.CommonsChunkPlugin({
        name: 'vendor',
        minChunks: function(module) {
          return (
            module.context && module.context.indexOf('node_modules') !== -1
          );
        },
      }),
      new webpack.optimize.CommonsChunkPlugin({
        name: 'manifest', //But since there are no more common modules between them we end up with just the runtime code included in the manifest file
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
    ],
  };

  if (isProd) {
    merchantConfig.plugins.push(
      new ExtractTextPlugin({
        filename: '[name]_[chunkhash].css',
      })
    );
  }

  return webpackMerge(commonConfig, merchantConfig);
};
