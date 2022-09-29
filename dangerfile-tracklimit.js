const bundleSizeCheck = require('./.github/danger/BundlesizeCheck');
const budgetConfig = require('./bundlesize-budget');

bundleSizeCheck({
  budgetConfig,
  stage: 'merge',
});
