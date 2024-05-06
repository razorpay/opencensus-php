const universeUsage = require('@razorpay/universe-cli/universeUsage').default;
const { BUNDLE_SIZE_CHECKS } = require('../constants');

const trackReport = ({ reportData, stage }) => {
  universeUsage.log({
    eventName: BUNDLE_SIZE_CHECKS,
    eventProperties: {
      module: universeUsage.modules.PR_REVIEW,
      prStage: stage,
      message: 'Bundle size threshold limit tracking',
      report: JSON.stringify(reportData),
      finalStatus: reportData.some((each) => !each.result),
    },
  });
};

module.exports = trackReport;
