const HtmlWebpackPlugin = require('html-webpack-plugin');
const cwd = require('process').cwd();

module.exports = (entry, vars) =>
  new HtmlWebpackPlugin({
    // disable auto-inject before </body>. we're controlling it via dot template
    inject: false,

    vars,

    template: cwd + '/web/templates/' + entry.template,
    filename: cwd + '/resources/views/' + entry.filename + '.blade.php',
  });
