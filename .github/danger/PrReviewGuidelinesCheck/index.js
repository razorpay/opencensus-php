const validatePrDescription = require('./validatePrDescription');
const checkJiraOrAsanaLink = require('./checkJiraOrAsanaLink');
const checkPrGuideLines = require('./checkPrGuideLines');
const { AdheredToGuidelineCheck, PrCheckSuccessCheck } = require('../constants');
const { pr } = require('../utils');

function prReviewGuidelinesCheck() {
  validatePrDescription(pr.body);
  checkJiraOrAsanaLink(pr.title, pr.body);
  checkPrGuideLines({ body: pr.body, checkType: AdheredToGuidelineCheck });
  checkPrGuideLines({ body: pr.body, checkType: PrCheckSuccessCheck });
}

module.exports = prReviewGuidelinesCheck;
