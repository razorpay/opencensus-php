const chalk = require('chalk');

/**
 * Logger
 * A utility class for logging messages to the console with different levels of severity.
 * @param {string} message - The message to log.
 */
class Logger {
  static log(message) {
    console.log(chalk.white(message));
  }

  static info(message) {
    console.log(chalk.blueBright.bold('[INFO] ') + chalk.blue(message));
  }

  static warn(message) {
    console.log(chalk.yellowBright.bold('[WARNING] ') + chalk.yellow(message));
  }

  static error(message) {
    console.log(chalk.redBright.bold('[ERROR] ') + chalk.red(message));
  }

  static success(message) {
    console.log(chalk.greenBright.bold('[SUCCESS] ') + chalk.green(message));
  }

  static table(data) {
    console.table(data);
  }

  static list(list) {
    list.forEach((item) => {
      console.log(chalk.white(`- ${item}`));
    });
  }
}

module.exports = Logger;
