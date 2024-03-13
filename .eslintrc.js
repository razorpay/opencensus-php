const universeEsLintConfig = require.resolve('@razorpay/universe-cli/eslintrc');
const {
  rules: smartLinterRules,
  pluginName: smartLinterPluginName,
  overrides: smartLinterOverrides,
} = require('./smart-linter');

module.exports = {
  root: true,
  extends: [universeEsLintConfig],
  plugins: [smartLinterPluginName],
  rules: {
    ...smartLinterRules,
  },
  overrides: smartLinterOverrides,
};
