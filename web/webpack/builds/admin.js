const webpack = require('webpack');
const InlineManifestWebpackPlugin = require('inline-manifest-webpack-plugin');
const path = require('path');

const isProd = require('process').env.NODE_ENV === 'production';
const commonConfig = require('./common');

// generated bladefiles
const htmlPlugins = require('../html')(
  {
    template: 'admin.jst',
    filename: 'adminIndex',
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
    libs: ['axios'],
    admin: './web/admin.js',
  },
  output: {
    path: __dirname + '/../../../public/dist/admin',
    filename: isProd ? '[name]-[hash].js' : '[name].js',
  },

  plugins: [
    new webpack.DllReferencePlugin({
      manifest: path.resolve(__dirname + '/../../../public/dist/manifest.json'),
    }),
    new webpack.optimize.CommonsChunkPlugin({
      names: ['libs', 'manifest'],
    }),
  ].concat(
    htmlPlugins,
    new InlineManifestWebpackPlugin({
      name: 'webpackManifest',
    })
  ),

  ...commonConfig,
};
