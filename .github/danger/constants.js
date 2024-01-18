const PR_AUTOMATED_CHECKS = 'PR_AUTOMATED_CHECKS';
const BUNDLE_SIZE_CHECKS = 'BUNDLE_SIZE_CHECKS';
const I18N_LINTER_CHECKS = 'I18N_LINTER_CHECKS';

const AdheredToGuidelineCheck = {
  // - [] Have you adhered to [Dashboard PR review guidelines]
  // - [x] Have you adhered to [Dashboard PR review guidelines]
  // - ✅ Have you adhered to [Dashboard PR review guidelines]
  queryRegex: /[ \S]*(?=Have you adhered to)/,
  logMsg: "Please check 'Have you adhered to [Dashboard PR review guidelines]'",
  reason: 'Dashboard PR review guidelines is not checked',
};

const PrCheckSuccessCheck = {
  // - [] Make sure all the checks are passed
  // - [x] Make sure all the checks are passed
  // - ✅Make sure all the checks are passed
  queryRegex: /[ \S]*(?=Make sure all the checks are passed)/,
  logMsg: "Please check 'Make sure all the checks are passed'",
  reason: 'Necessary checks are not passed',
};

module.exports = {
  PR_AUTOMATED_CHECKS,
  BUNDLE_SIZE_CHECKS,
  I18N_LINTER_CHECKS,
  AdheredToGuidelineCheck,
  PrCheckSuccessCheck,
};
