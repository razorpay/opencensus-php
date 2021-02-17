const utils = require('./utils');

module.exports = () => {
  // Start the static server
  global.server = utils.startServer();
};
