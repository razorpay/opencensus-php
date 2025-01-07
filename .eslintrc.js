const universeEsLintConfig = require.resolve('@razorpay/universe-cli/eslintrc');
const {
  rules: smartLinterRules,
  pluginName: smartLinterPluginName,
  overrides: smartLinterOverrides,
} = require('./smart-linter');

module.exports = {
  root: true,
  extends: [universeEsLintConfig],
  plugins: [smartLinterPluginName, 'no-dist-assets'],
  rules: {
    ...smartLinterRules,
    'no-dist-assets/no-dist-assets': 'error',
  },
  overrides: smartLinterOverrides,
};
