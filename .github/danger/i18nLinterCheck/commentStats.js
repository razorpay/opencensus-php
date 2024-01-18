const { markdown } = require('@razorpay/universe-doctor/src/configs/danger/utils/constants');
const { createMarkdownTable } = require('../utils');

/**
 * Creates table data from the report data.
 * @param {Object[]} reportData - The report data containing file, ruleId, and message.
 * @returns {Array[]} The table data to be used for markdown table creation.
 */
const getTableData = (reportData) =>
  reportData.map(({ file, ruleId, message }) => [file, message, ruleId]);

/**
 * Generates comment stats and adds comments to the markdown.
 * @param {Object[]} reportData - The report data to generate comment stats.
 */
const commentStats = (reportData) => {
  markdown('<br>');

  markdown(
    createMarkdownTable([['File Path', 'Error', 'Eslint Rule Id'], ...getTableData(reportData)]),
  );

  // Tags the @razorpay/i18n-fe for PR review
  markdown('@razorpay/i18n-fe please review this PR once.');

  markdown('<br>');
};

module.exports = commentStats;
