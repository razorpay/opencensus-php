// eslint-disable-next-line import/extensions
const universeDangerJs = require('@razorpay/universe-doctor/src/configs/danger.web');

// sensitive files pertaining to Frontend
const frontendSensitiveFiles = [
  'package.json',
  'web/package.json',
  'web/tsconfig.json',
  'web/webpack.client.js',
  'web/tools/build.js',
  'web/entry/**',
];

// sensitive files pertaining to Backend
const backendSensitiveFiles = [];

universeDangerJs({
  enforceExactPackageVersions: 'off',
  validatePRDescription: 'off',
  isLockFileSynchronised: 'off',
  checkPRReviewer: 'off',
  checkMissingTests: 'off',
  checkPRAssignee: 'off',
  checkPRSize: {
    value: 600,
    type: 'warn',
  },
  checkSensitiveFiles: {
    extendIncludePattern: [...frontendSensitiveFiles, ...backendSensitiveFiles],
  },
});
