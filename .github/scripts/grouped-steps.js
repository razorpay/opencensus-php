const COLOR_BLUE = '\x1b[38;2;100;149;237m'; // RGB escape sequence for #6495ED
const COLOR_RESET = '\x1b[0m'; // Reset to default

/**
 * Starts a collapsible log group like GitHub Actions.
 **/
function startGroup(groupTitle) {
  console.log(`::group::${COLOR_BLUE}${groupTitle}${COLOR_RESET}`);
}

/**
 * Ends a collapsible log group like GitHub Actions.
 **/
function endGroup() {
  console.log(`::endgroup::`);
}

/**
 *
 * @param {string} groupTitle - The title of the group to be displayed in the logs.
 * @example
 * const { startGroup, endGroup } = require('./group-logger');
 *
 * startGroup('Install Dependencies');
 * console.log('Installing dependencies...');
 * endGroup();
 */
module.exports = {
  startGroup,
  endGroup,
};
