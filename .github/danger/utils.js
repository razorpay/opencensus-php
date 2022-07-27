const printMessage = require('@razorpay/universe-doctor/src/configs/danger/utils/printMessage');
const universeUsage = require('@razorpay/universe-utils/universeUsage').default;

module.exports = {
  printMessage,
  pr: global.danger.github.pr,
  universeUsage,
};
