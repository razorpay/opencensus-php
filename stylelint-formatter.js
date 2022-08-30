// eslint-disable-next-line
/**
 * @type {import('stylelint').Formatter}
 */
function formatter(results) {
  // Converts stylelint output into eslint report so ataylorme/eslint-annotate-action@v2 in format-checker action can annotate files
  const _results = results.map((result) => {
    let errorCount = 0;
    let warningCount = 0;
    const messages = [];

    result.warnings.forEach((warning) => {
      const isError = warning.severity === 'error';
      messages.push({
        ruleId: warning.rule,
        line: warning.line,
        column: warning.column,
        severity: isError ? 3 : 2,
        message: warning.text,
      });

      if (isError) {
        errorCount += 1;
      } else {
        warningCount += 1;
      }
    });

    return {
      filePath: result.source,
      errorCount,
      warningCount,
      messages,
    };
  });

  return JSON.stringify(_results);
}

module.exports = formatter;
