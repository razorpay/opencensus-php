const { setOutput } = require('@actions/core');
const validateStateToThreshold = require('./validateStatsToThreshold');
const generateReportData = require('./generateReportData');
const commentStats = require('./commentStats');
const trackSizeChangeReport = require('./trackSizeChangeReport');
const generateSlackResponse = require('./generateSlackResponse');
const { logger } = require('./utils');
const { pr, printMessage } = require('../utils');

const getStats = () => {
  let stats = [];
  try {
    stats = require('../../../public/dist/merchant-stats');
  } catch (error) {
    logger.error('File not Found', error);
  }
  return stats;
};

const setPrDetails = () => {
  const { html_url, user: { login, avatar_url } = {} } = pr;
  setOutput('prLink', html_url);
  setOutput('prOwner', login);
  setOutput('prAvatar', avatar_url);
};

const ValidateBundleAssets = ({ budgetConfig, stage }) => {
  logger.info(`Starting Valition`);

  const bundleStats = getStats();

  if (!budgetConfig) {
    logger.error('Budget file is not present');
    throw new Error('Budget file is not present');
  }
  if (!bundleStats || (bundleStats && bundleStats.length === 0)) {
    logger.error('Error in bundleStats file, Please check build once');
    printMessage({
      type: 'fail',
      message: 'Error in Bundle Stats file, Please check build once',
    });
    return;
  }

  const bundleObj = validateStateToThreshold({
    budgetConfig,
    bundleStats,
  });

  if (!bundleObj || (bundleObj && bundleObj.length === 0)) {
    printMessage({
      type: 'warn',
      message: 'No Matched bundles found in Threshold budget',
    });
    logger.warn(`No Matched bundles found`);
    return;
  } else {
    logger.info(`Fetched Bundle size report data`);
  }

  const reportData = generateReportData({ bundleObj });

  logger.info(`Report generated`);

  setOutput('bundleSizeReport', JSON.stringify(reportData));

  if (stage === 'commit') {
    pr && setPrDetails();
    commentStats({ reportData });
  }

  if (stage === 'merge') {
    generateSlackResponse({ reportData });
  }

  trackSizeChangeReport({ reportData, stage });
};

module.exports = ValidateBundleAssets;
