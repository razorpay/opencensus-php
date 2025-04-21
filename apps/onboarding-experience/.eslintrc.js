const universeEsLintConfig = require.resolve('@razorpay/universe-cli/eslintrc.typescript');

module.exports = {
  extends: [universeEsLintConfig],
  parserOptions: {
    tsconfigRootDir: __dirname,
    project: ['./tsconfig.json'],
  },
};
