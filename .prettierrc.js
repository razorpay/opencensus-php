const universePrettierConfig = require('./web/node_modules/@razorpay/universe-doctor/prettierrc');

module.exports = {
  ...universePrettierConfig,
  printWidth: 100,
  tabWidth: 2,
  useTabs: false,
  semi: true,
  singleQuote: true,
  jsxSingleQuote: false,
  trailingComma: 'all',
  bracketSpacing: true,
  jsxBracketSameLine: false,
  arrowParens: 'always',
};
