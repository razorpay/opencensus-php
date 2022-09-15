module.exports = {
  plugins: ['stylelint-stylus'],
  extends: ['stylelint-config-standard', 'stylelint-stylus/standard'],
  rules: {
    'stylus/selector-list-comma': ['always'],
    'declaration-no-important': [true],
    'color-named': ['never'],
    'max-nesting-depth': [4],
    'stylus/declaration-colon': null,
    'stylus/semicolon': null,
    'selector-class-pattern': null,
    'alpha-value-notation': ['number'],
    'stylus/pythonic': null,
    'stylus/color-hex-case': 'upper',
  },
};
