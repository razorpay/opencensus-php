const { universeUsage } = require('../utils');
const { I18N_LINTER_CHECKS } = require('../constants');

const trackI18nLinterReport = ({ prNumber, prRaisedBy, errorCodeCountMap }) => {
  Object.entries(errorCodeCountMap).forEach(([lintRule, errorCount]) => {
    universeUsage.log({
      eventName: I18N_LINTER_CHECKS,
      eventProperties: {
        module: universeUsage.modules.PR_REVIEW,
        lintRule, // Error linter rule ex: i18n-rules/no-region-specific-keyword
        errorCount, // No of errors for that particular rule
        prNumber, // PR number raised by developer
        prRaisedBy, //  PR author github id
        projectName: 'dashboard', // project name
      },
    });
  });
};

module.exports = trackI18nLinterReport;
