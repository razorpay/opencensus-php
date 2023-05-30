const { setFailed } = require('@actions/core');
const { printMessage, universeUsage } = require('../utils');
const { PR_AUTOMATED_CHECKS } = require('../constants');

const jiraIssueIdRegex = /((?<!([A-Za-z]{1,10})-?)[A-Z]+-\d+)/;
const asanaRegex = /(?<=https:\/\/app.asana.com)[.\S]*/;

function checkJiraOrAsanaLink(title, body) {
  let hasIssueId = false;
  const jiraIssueIds = body.match(jiraIssueIdRegex) || title.match(jiraIssueIdRegex);

  if (jiraIssueIds) {
    hasIssueId = jiraIssueIds.filter(Boolean).length > 0;
  }

  const asanaContent = body.match(asanaRegex);
  const hasAsanaLink = asanaContent ? asanaContent[0].length > 5 : false;

  if (!hasIssueId && !hasAsanaLink) {
    const type = 'fail';
    const message =
      'Jira/Asana link is missing. For example, you can add `[SSAB-210]` in the title or \n `https://razorpay.atlassian.net/browse/SSAB-210` or `https://app.asana.com/0/1201613071695653/1202552294564647/f` in the description';
    printMessage({
      type,
      message,
    });
    setFailed(message);
    universeUsage.log({
      eventName: PR_AUTOMATED_CHECKS,
      eventProperties: {
        module: universeUsage.modules.PR_REVIEW,
        reason: 'Missing Jira/Asana link',
        message,
        type,
      },
    });
  }
}

module.exports = checkJiraOrAsanaLink;
