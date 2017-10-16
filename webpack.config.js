const webpack = require('webpack');
const isProd = require('process').env.NODE_ENV === 'production';

// generated bladefiles
const htmlPlugins = require('./web/webpack/html')(
  {
    template: 'admin.jst',
    filename: 'adminIndex',
  },
  {
    // template locals
    cdnUrl: isProd ? 'https://cdn.razorpay.com/dashboard' : '/dist',
    filename: (_, name) =>
      _.webpackConfig.output.filename.replace('[name]', name),
  }
);

let plugins = [htmlPlugins];

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

  resolveLoader: {
    alias: {
      'dot-loader': __dirname + '/web/webpack/dot-loader.js',
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

  plugins,

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
            presets: [
              'env',
              ['es2015', { loose: true, modules: false }],
              'react',
              'stage-0',
            ],
            plugins: ['transform-decorators-legacy', 'react-html-attrs'],
          },
        },
      },
    ],
  },
};
