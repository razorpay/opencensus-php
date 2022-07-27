const { printMessage, universeUsage } = require('./utils');
const { PR_AUTOMATED_CHECKS } = require('./constants');

const adheredToGuidelinesRegex = /[ \S]*(?=Have you adhered to)/;
function checkAdheredToGuidelines(body) {
  // - [] Have you adhered to [Dashboard PR review guidelines]
  // - [x] Have you adhered to [Dashboard PR review guidelines]
  let isChecked = false;
  const guidelinesLine = body.match(adheredToGuidelinesRegex);
  if (guidelinesLine) {
    isChecked = !!guidelinesLine[0].match(/\[x\]/);
  }
  if (!isChecked) {
    const type = 'fail';
    const message = "Please check 'Have you adhered to [Dashboard PR review guidelines]'";
    printMessage({
      type,
      message,
    });
    universeUsage.log({
      eventName: PR_AUTOMATED_CHECKS,
      eventProperties: {
        module: universeUsage.modules.PR_REVIEW,
        reason: 'Dashboard PR review guidelines is not checked',
        message,
        type,
      },
    });
  }
}

module.exports = checkAdheredToGuidelines;
