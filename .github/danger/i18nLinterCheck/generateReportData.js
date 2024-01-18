const { readFileSync } = require('fs');
const { logger } = require('./utils');

/**
 * Reads the content of the ESLint report.
 * @returns {Object|null} The parsed content of the ESLint report or null if an error occurs.
 */
const readESLintReport = () => {
  try {
    const reportContent = readFileSync('eslint_report.json', 'utf-8');
    return JSON.parse(reportContent);
  } catch (error) {
    logger.error(`Error reading eslint_report.json ::`, error);
    return null;
  }
};

/**
 * Creates ESLint messages from raw report content.
 * @param {Object[]} rawReportContent - Raw content of the ESLint report.
 * @returns {Object} Object containing ESLint report, error count, and error code count map.
 *                   - `report`: {
 *                                file: 'file/path/example.js',
 *                                ruleId: 'i18n-rules/no-href-hardcoding',
 *                                message: 'Variable is defined but never used.',
 *                              }
 *                   - `errorCount`: The total count of errors found in the ESLint report.
 *                   - `errorCodeCountMap`: An object mapping ESLint rule IDs to their respective counts of occurrences within the report.
 *                                          Example:
 *                                          {
 *                                            'i18n-rules/no-href-hardcoding': 5,
 *                                            'i18n-rules/no-currency-hardcoding': 8,
 *                                            ...
 *                                          }
 */
function createESLintMessages(rawReportContent) {
  let errorCount = 0;
  const errorCodeCountMap = {};

  const report = rawReportContent.flatMap((file) => {
    errorCount += file.errorCount;

    return file.messages.map(({ ruleId, message }) => {
      errorCodeCountMap[ruleId] = (errorCodeCountMap[ruleId] || 0) + 1;

      return {
        file: file.filePath.replace('/runner/_work/dashboard/dashboard/', ''),
        ruleId,
        message,
      };
    });
  });

  return {
    report,
    errorCount,
    errorCodeCountMap,
  };
}

const eslintReports = readESLintReport();
const eslintMessages = createESLintMessages(eslintReports);

module.exports = eslintMessages;
