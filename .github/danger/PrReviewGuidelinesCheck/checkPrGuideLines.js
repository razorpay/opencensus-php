const { printMessage, universeUsage } = require('../utils');
const { PR_AUTOMATED_CHECKS } = require('../constants');
const checkboxRegex = /\[x\]/i;
const emojiCheckboxRegex = /\u2705/;

function checkPrGuideLines({ body, checkType }) {
  const { queryRegex, logMsg, reason } = checkType;

  let isChecked = false;
  const guidelinesLine = body.match(queryRegex);
  if (guidelinesLine) {
    isChecked =
      !!guidelinesLine[0].match(checkboxRegex) || !!guidelinesLine[0].match(emojiCheckboxRegex);
  }
  if (!isChecked) {
    const type = 'fail';
    printMessage({
      type,
      message: logMsg,
    });
    universeUsage.log({
      eventName: PR_AUTOMATED_CHECKS,
      eventProperties: {
        module: universeUsage.modules.PR_REVIEW,
        reason,
        message: logMsg,
        type,
      },
    });
  }
}

module.exports = checkPrGuideLines;
