const webpack = require('webpack');
const path = require('path');

const adminConfig = require('./builds/admin');
const merchantConfig = require('./builds/merchant');

module.exports = [
  {
    name: 'vendor',
    entry: ['react', 'react-dom', 'react-router-dom', 'mobx', 'mobx-react'],
    output: {
      path: __dirname + '/../../public/dist',
      filename: 'vendor.js',
      library: 'vendor_[hash]',
    },
    plugins: [
      new webpack.DllPlugin({
        name: 'vendor_[hash]',
        path: path.resolve(__dirname, '/../../public/dist/manifest.json'),
      }),
    ],
  },
  adminConfig,
  merchantConfig,
];
