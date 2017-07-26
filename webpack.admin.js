'use strict';

const fs = require('fs');
const path = require('path');
const webpack = require('webpack');
const bootstrap = require('bootstrap-styl');
const webpackMerge = require('webpack-merge');

// Admin Rev Webpack plugin.
// This plugin replaces the react asset placeholder in app.js with webpack's compilation result
// Can be removed once the admin is entirely migrated to react
function AdminRevPlugin() {}

AdminRevPlugin.prototype.getAdminDist = () => {
  const distPath = path.resolve(__dirname, 'public/js/generated');
  var list = fs.readdirSync(distPath);
  var adminDistFile = list.filter(file => file.indexOf('admin') !== -1)[0];
  return `${distPath}/${adminDistFile}`;
};

AdminRevPlugin.prototype.apply = function(compiler) {
  compiler.plugin('done', stats => {
    const adminDist = this.getAdminDist();
    stats = stats.toJson();
    var contents = String(fs.readFileSync(adminDist));
    fs.writeFileSync(
      adminDist,
      contents.replace(/<%=REACT_REV_PLACEHOLDER=%>/, match => {
        return stats.publicPath + stats.assetsByChunkName.admin;
      })
    );
  });
};

module.exports = (commonConfig, isProd) => {
  const adminConfig = {
    entry: {
      admin: './admin/index',
    },
    module: {
      rules: [
        {
          test: /\.styl$/,
          loader: [
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
    plugins: [new AdminRevPlugin()],
  };

  return webpackMerge(commonConfig, adminConfig);
};
