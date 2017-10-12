const webpack = require('webpack');
const path = require('path');
const UglifyJSPlugin = require('uglifyjs-webpack-plugin');

const adminConfig = require('./builds/admin');
const merchantConfig = require('./builds/merchant');
const common = require('./builds/common');

module.exports = [
  {
    name: 'vendor',
    entry: ['react', 'react-dom', 'react-router-dom', 'mobx', 'mobx-react'],
    output: {
      path: __dirname + '/../../public/dist',
      filename: 'vendor.js',
      library: 'vendor_[hash]',
    },
    plugins: common.plugins.concat([
      new webpack.DllPlugin({
        name: 'vendor_[hash]',
        path: path.resolve(__dirname + '/../../public/dist/manifest.json'),
      }),
    ]),
  },
  adminConfig,
  // merchantConfig,
];
