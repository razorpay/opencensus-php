const chalk = require('ansi-colors');

class Logger {
  constructor(prefix) {
    this.prefix = prefix;
  }

  info = (message) => {
    console.log(chalk.cyan(`[INFO] ${this.#messageWithPrefix(message)}`));
  };

  warn = (message) => {
    console.log(chalk.yellow(`[WARN] ${this.#messageWithPrefix(message)}`));
  };

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

  #messageWithPrefix = (message) => {
    return this.prefix ? `${this.prefix}: ${message}` : message;
  };
}

const logger = new Logger('Bundle Size Check');

const bytesToKB = (bytes, decimals = 2) => {
  if (!+bytes) return 0;
  return parseFloat((bytes / 1024).toFixed(decimals));
};

const bold = (text) => `<b>${text}</b>`;

const getStatus = (result, withIcon = true) =>
  result ? `${withIcon ? '✅ ' : ''}Pass` : `${withIcon ? '❌ ' : ''}${bold('Fail')}`;

const showThreshold = ({ usedThreshold }) =>
  `${usedThreshold} % ${usedThreshold > 100 ? '🚨' : usedThreshold > 95 ? '🚧' : ''}`;

module.exports = {
  logger,
  bytesToKB,
  getStatus,
  showThreshold,
  bold,
};
