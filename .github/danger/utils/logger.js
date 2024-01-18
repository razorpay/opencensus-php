const chalk = require('ansi-colors');

/**
 * Logger class for displaying formatted log messages.
 */
class Logger {
  /**
   * @param {string} prefix - Prefix to be added to log messages (optional).
   */
  constructor(prefix) {
    this.prefix = prefix;
  }

  /**
   * Logs an informational message.
   * @param {string} message - Informational message to be logged.
   */
  info = (message) => {
    console.log(chalk.cyan(`[INFO] ${this.#messageWithPrefix(message)}`));
  };

  /**
   * Logs a warning message.
   * @param {string} message - Warning message to be logged.
   */
  warn = (message) => {
    console.log(chalk.yellow(`[WARN] ${this.#messageWithPrefix(message)}`));
  };

  /**
   * Logs an error message along with an optional error object.
   * @param {string} message - Error message to be logged.
   * @param {Error|string} [err] - Optional error object or string.
   */
  error = (message, err) => {
    console.error(chalk.red(`[ERROR] ${this.#messageWithPrefix(message)}`));
    if (err) {
      if (err instanceof Error) {
        console.error(err);
      } else {
        console.error(chalk.red(err));
      }
    }
  };

  /**
   * Adds prefix to the provided message.
   * @param {string} message - Message to prepend with prefix.
   * @returns {string} - Message with or without prefix based on constructor setting.
   * @private
   */
  #messageWithPrefix = (message) => {
    return this.prefix ? `${this.prefix}: ${message}` : message;
  };
}

module.exports = Logger;
