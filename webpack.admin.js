'use strict';

const fs = require('fs');
const path = require('path');
const webpack = require('webpack');
const HtmlWebpackPlugin = require('html-webpack-plugin');
const bootstrap = require('bootstrap-styl');
const webpackMerge = require('webpack-merge');

// Admin Rev Webpack plugin.
// This plugin replaces the react asset placeholder in app.js with webpack's compilation result
// Can be removed once the admin is entirely migrated to react
function AdminRevPlugin(options) {}

AdminRevPlugin.prototype.getAdminDist = () => {
  const distPath = path.resolve(__dirname, 'public/js/generated');
  var list = fs.readdirSync(distPath);
  var adminDistFiles = list.filter(file => file.indexOf('admin') !== -1);
  var lastModifiedAdminDist = adminDistFiles.reduce(function(
    previousFile,
    currentFile
  ) {
    var prevStat = fs.statSync(`${distPath}/${previousFile}`);
    var currentStat = fs.statSync(`${distPath}/${currentFile}`);
    return +currentStat.mtime > +prevStat.mtime ? currentFile : previousFile;
  });
  return `${distPath}/${lastModifiedAdminDist}`;
};

AdminRevPlugin.prototype.apply = function(compiler) {
  compiler.plugin('compilation', compilation => {
    const adminDist = this.getAdminDist();

    compilation.plugin(
      'html-webpack-plugin-alter-chunks',
      (chunks, options) => {
        let htmlWebpackPlugin = options.plugin;
        var assets = htmlWebpackPlugin.htmlWebpackPluginAssets(
          compilation,
          chunks
        );
        var contents = String(fs.readFileSync(adminDist));
        fs.writeFileSync(
          adminDist,
          contents.replace(/<%=REACT_REV_PLACEHOLDER=%>/, match => {
            return assets.chunks.admin.entry;
          })
        );
        return chunks;
      }
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
    plugins: [
      new HtmlWebpackPlugin({
        inject: false,
      }),
      new AdminRevPlugin(),
    ],
  };

  return webpackMerge(commonConfig, adminConfig);
};
