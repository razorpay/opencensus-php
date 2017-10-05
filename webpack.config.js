const webpack = require('webpack');
const isProd = require('process').env.NODE_ENV === 'production';

// generated bladefiles
const htmlPlugins = require('./web/webpack/html')(
  {
    'merchant.jst': 'merchantIndex',
    'admin.jst': 'adminIndex',
  },
  {
    // template locals
    cdnUrl: isProd ? 'https://cdn.razorpay.com/dashboard' : '/dist',

    filename: function(_, name) {
      return _.webpackConfig.output.filename
        .replace('[name]', name)
        .replace('[hash]', _.webpack.hash);
    },
  }
);

module.exports = {
  entry: {
    vendor: ['react', 'react-dom', 'react-router-dom', 'mobx', 'mobx-react'],
    merchant: './web/merchant.js',
    admin: './web/admin.js',
  },

  output: {
    path: __dirname + '/public/dist',
    filename: isProd ? '[name]-[hash].js' : '[name].js',
  },

  resolve: {
    modules: ['node_modules', 'web/modules'],
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

  plugins: [
    new webpack.optimize.CommonsChunkPlugin({
      name: 'vendor',
      minChunks: Infinity,
    }),
  ].concat(htmlPlugins),

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
