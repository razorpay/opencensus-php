const UglifyJSPlugin = require('uglifyjs-webpack-plugin');
const webpack = require('webpack');
const isProd = require('process').env.NODE_ENV === 'production';

let plugins = [];

module.exports = {
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
    admin: './admin.js',
    merchant: './js/merchant/index.js',
  },

  output: {
    path: __dirname + '/../public/dist',
    filename: '[name].js',
  },

  resolve: {
    modules: ['js', 'node_modules'],
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
        test: /\.js$/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader',
          options: {
            presets: ['env', 'react', 'stage-0'],
            plugins: [
              'transform-decorators-legacy',
              'react-html-attrs',
            ],
          },
        },
      },
    ],
  },

  devtool: isProd ? false : false,

  plugins,
};

if (isProd) {
  plugins.push(
    new UglifyJSPlugin({
      sourceMap: true,
    })
  );
}
