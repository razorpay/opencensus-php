const path = require('path');

// Map eslint integer values to sonarqube severity strings.
const severity = {
  1: 'MINOR',
  2: 'CRITICAL',
};

// Map eslint severity integer values to sonarqube issue type strings.
const type = {
  1: 'CODE_SMELL',
  2: 'BUG',
};

module.exports = (results) => {
  const issues = [];
  results.forEach((result) => {
    let relativePath = path.relative('', result.filePath);
    relativePath = relativePath.replace('\\', '/');
    result.messages.forEach((message) => {
      if (message.ruleId.includes('i18n-rules')) {
        issues.push({
          engineId: 'eslint',
          ruleId: message.ruleId,
          severity: severity[message.severity],
          type: type[message.severity],
          tags: ['i18n-linter'],
          primaryLocation: {
            message: message.message,
            filePath: relativePath,
            textRange: {
              startLine: message.line,
            },
          },
        });
      }
    });
  });

  return JSON.stringify({ issues });
};
