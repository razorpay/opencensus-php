const webpack = require('webpack');
const isProd = require('process').env.NODE_ENV === 'production';

module.exports = {
  externals: [].reduce.call(
    (process.env.externals || '').split(' '),
    (prev, next, index, arr) => {
      if (index % 2) {
        prev[next.split('/')[0]] = arr[index - 1];
      }
      return prev;
    },
    {}
  ),

  entry: {
    admin: './web/admin.js',
  },

  output: {
    path: __dirname + '/public/dist/admin',
    filename: '[name].js',
  },

  resolve: {
    modules: ['web/js', 'node_modules'],
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
            plugins: ['transform-decorators-legacy', 'react-html-attrs'],
          },
        },
      },
    ],
  },
};
