const webpack = require('webpack');
const argv = require('yargs').argv;
const projects = [argv.project];
// mini css extract plugin is breaking when builds are running for multiple projects in the same process
// let projects = ['merchant', 'merchantLA', 'razorx'];
const async = require('neo-async');
const paths = require('@universe/configs/paths');
const chalk = require('chalk');
const isDevelopment = process.env.STAGE === 'development';
const babelConfig = require('../.babelrc.json');
const universeWebpackClientConfig = require(paths.universeConfigs.webpackClientConfig)({
  babelConfig,
});
const statsOptions = require('./stats');

const build = (project) => {
  const webpackClientConfig = require(paths.consumer.webpackClientConfig)({
    config: universeWebpackClientConfig,
    project,
  });
  return new Promise((resolve) => {
    const finish = (err, stats) => {
      // Handle webpack configuration errors
      if (err) {
        console.error(chalk.red(err.stack || err));
        if (err.details) {
          console.error(chalk.red(err.details));
        }
        // eslint-disable-next-line no-process-exit
        process.exit(1);
      }

      const info = stats.toJson('minimal');

      // Handle compilation errors
      if (stats.hasErrors()) {
        console.error(chalk.red(info.errors));
        if (!isDevelopment) {
          // eslint-disable-next-line no-process-exit
          process.exit(1);
        }
      }

      // Print any warnings before anything else
      if (stats.hasWarnings()) {
        console.warn(chalk.yellow(info.warnings));
      }
      console.log(stats.toString(statsOptions));
      resolve();
    };
    if (isDevelopment) {
      webpack(webpackClientConfig).watch({}, finish);
    } else {
      webpack(webpackClientConfig).run(finish);
    }
  });
};
// var run = require('parallel-webpack').run,
//     configPath = require.resolve('./temp.js');

// run(configPath, {
//     watch: false,
//     maxRetries: 1,
//     stats: true, // defaults to false
// });
// webpack([
//   build('merchant'),
//   build('merchantLA')
// ], (err, stats) => { // Stats Object
//   if(err) {
//     throw err;
//   }
//   process.stdout.write(stats.toString() + '\n');
// })

async.eachSeries(
  projects,
  (project, done) => {
    build(project)
      .then(() => done())
      .catch((err) => done(err));
  },
  (err) => {
    if (err) {
      throw err;
    }
  },
);
