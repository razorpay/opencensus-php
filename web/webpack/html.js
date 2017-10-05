const HtmlWebpackPlugin = require('html-webpack-plugin');
const cwd = require('process').cwd();

module.exports = (entries, vars) =>
  Object.entries(entries).map(
    ([template, filename]) =>
      new HtmlWebpackPlugin({
        // disable auto-inject before </body>. we're controlling it via dot template
        inject: false,

        vars,

        template: cwd + '/web/templates/' + template,
        filename: cwd + '/resources/views/' + filename + '.blade.php',
      })
  );
