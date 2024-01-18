const { pr } = require('../utils');
const { logger } = require('./utils');
const commentStats = require('./commentStats');
const trackI18nLinterReport = require('./trackI18nLinterReport');
const generateReportData = require('./generateReportData');
const { printMessage } = require('../utils');

const validateWithI18nLinter = () => {
  try {
    if (generateReportData.errorCount === 0) return;

    // Comments the reports in PR
    commentStats(generateReportData.report);

    // Add PR failing message in PR.
    printMessage({
      type: 'fail',
      message: `Check the [i18n Coding Guidelines document](https://docs.google.com/document/d/1JuSyGB8d961TQ24ocFpp7UhVPreR5Lm38hlS1Ed2lhQ/edit#heading=h.nzbwdi45f65o) for tips on writing code that isn't specific to any particular region or location.`,
    });

    trackI18nLinterReport({
      errorCodeCountMap: generateReportData.errorCodeCountMap,
      prNumber: pr.number,
      prRaisedBy: pr.user.login,
    });
  } catch (error) {
    logger.error('An error occurred while validating with i18n linter:', error);
  }
};

module.exports = validateWithI18nLinter;
