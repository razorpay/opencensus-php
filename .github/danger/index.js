const validatePrDescription = require('./validatePrDescription');
const checkJiraOrAsanaLink = require('./checkJiraOrAsanaLink');
const checkAdheredToGuidelines = require('./checkAdheredToGuidelines');
const { pr } = require('./utils');

function prReviewGuidelinesCheck() {
  validatePrDescription(pr.body);
  checkJiraOrAsanaLink(pr.title, pr.body);
  checkAdheredToGuidelines(pr.body);
}

module.exports = prReviewGuidelinesCheck;
