const { printMessage, universeUsage } = require('../utils');
const { PR_AUTOMATED_CHECKS } = require('../constants');

const matchAllRegex = /[.\s\S]*/;
const headings = [
  /(?=\d\. What is this PR about\?)/,
  /(?=What is this PR about\?)/,
  /(?=\d\. Write individual changes in points)/,
  /(?=Write individual changes in points)/,
  /(?=\*\*List of impacted parts in dashboard\*\*)/,
  /(?=\*\*Responsiveness\*\*)/,
  /(?=\*\*Dependencies\*\*)/,
  /(?=\*\*QA sign off done by?\*\*)/,
  /(?=\*\*Check List\*\*)/,
];

function getContentOfAPattern(body, pattern, patternsToBeRemoved = []) {
  // Matches all the content after the pattern
  let matchedString = body.match(new RegExp(pattern.source + matchAllRegex.source));

  if (matchedString) {
    matchedString = matchedString[0];
    headings.forEach((headingPattern) => {
      // matches all the content before the pattern
      const contentBeforePattern = matchedString.match(
        new RegExp(matchAllRegex.source + headingPattern.source),
      );
      if (contentBeforePattern) {
        matchedString = contentBeforePattern[0];
      }
    });
    patternsToBeRemoved.forEach((pattern) => {
      matchedString = matchedString.replace(pattern, '');
    });
    matchedString = matchedString.replace('---', '');
    matchedString = matchedString.trim();
  }
  return matchedString || '';
}

//  Checks if description has one of these two sections
// 1. What is this PR about?
// 2. Write individual changes in points

function validatePrDescription(body) {
  const prAboutRegex = /(?<=What is this PR about\?)/;
  const individualPointsRegex = /(?<=Write individual changes in points)/;

  const prAboutContent = getContentOfAPattern(body, prAboutRegex);
  if (prAboutContent.length <= 5) {
    const type = 'fail';
    const message = "'What is this PR about' section missing";

    printMessage({
      type,
      message,
    });
    universeUsage.log({
      eventName: PR_AUTOMATED_CHECKS,
      eventProperties: {
        module: universeUsage.modules.PR_REVIEW,
        reason: 'Missing PR About Section',
        message,
        type,
      },
    });
  } else if (!prAboutContent) {
    const individualPointsContent = getContentOfAPattern(body, individualPointsRegex, [
      '[Screenshot with each point if necessary]',
    ]);

    if (individualPointsContent.length <= 5) {
      const type = 'fail';
      const message = "'Write individual changes in points' section missing";

      printMessage({
        type,
        message,
      });

      universeUsage.log({
        eventName: universeUsage.events.UNIVERSE_DOCTOR_REPORT,
        eventProperties: {
          module: universeUsage.modules.PR_REVIEW,
          reason: 'Missing Individual Changes Section',
          message,
          type,
        },
      });
    }
  }
}

module.exports = validatePrDescription;
