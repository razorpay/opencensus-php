const { setFailed, setOutput } = require('@actions/core');
const { markdown } = require('@razorpay/universe-doctor/src/configs/danger/utils/constants');
const printMessage = require('@razorpay/universe-doctor/src/configs/danger/utils/printMessage');

const { logger } = require('./utils');
const { getStatus, showThreshold, bold, createMarkdownTable } = require('../utils');

const truncLabel = (text, limit = 50) =>
  text.length > limit
    ? `<span title=${text}>${text.slice(0, limit)}...<span>`
    : `<span>${text}</span>`;

const getTableData = ({ reportData }) =>
  reportData.map((each) => {
    return [
      getStatus(each.result),
      truncLabel(each.label),
      each.displaySize,
      each.budget.limit || '-',
      showThreshold(each),
    ];
  });

const commentStats = ({ reportData }) => {
  const isThresholdBreached = reportData.some((each) => !each.result);

  setOutput('isThresholdBreached', isThresholdBreached);

  markdown(`<h3>Final Result:  ${isThresholdBreached ? `❌ ${bold('Fail')}` : '✅ Pass'}</h3>`);

  markdown(`<br>`);

  markdown(
    createMarkdownTable([
      ['Status', 'Path', 'Current Size', 'Max Size', 'Used Limit'],
      ...getTableData({ reportData }),
    ]),
  );

  markdown(`<br>`);

  markdown(`<h2>${bold('Bundle Size Report')}</b> 📂 ☠️</h2>`);

  if (isThresholdBreached) {
    logger.error(
      'Size limit for bundles breached threshold limits, Please Refer to Size limit report. Try to reduce size or update budget',
    );
    printMessage({
      type: 'fail',
      message:
        'Size limit for bundles breached threshold limits, Please Refer to Size limit report. Try to reduce size or update budget',
    });
    setFailed('Size limit for bundles breached threshold limit');
  }
};

module.exports = commentStats;
