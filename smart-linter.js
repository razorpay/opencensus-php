// Helper function to generate rule configurations
const createRulesConfig = (setting) => ({
  'i18n-rules/no-currency-hardcoding': setting,
  'i18n-rules/no-region-specific-keyword': setting,
  'i18n-rules/no-href-hardcoding': setting,
  'i18n-rules/no-use-of-deprecated-functions': setting,
  'i18n-rules/no-hardcoded-i18n-types': setting,
  'i18n-rules/no-region-specific-image': setting,
});

const rules = createRulesConfig('warn');
const overrides = [
  {
    // Disabling i18n rules for test files and e2e tests
    files: [
      '**/__tests__/**/*.[jt]s?(x)',
      '**/__test__/**/*.[jt]s?(x)',
      '**/*.test.**/*.[jt]s?(x)',
      '**/e2e/**/*.[jt]s?(x)',
      '**/mocks/**/*.[jt]s?(x)',
    ],
    rules: createRulesConfig('off'), // Disable rules for specified files
  },
];

const pluginName = 'i18n-rules';

module.exports = {
  createRulesConfig,
  rules,
  overrides,
  pluginName,
};
