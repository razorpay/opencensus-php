const universeEsLintConfig = require('@universe/configs/eslintrc');

module.exports = {
  ...universeEsLintConfig,
  rules: {
    ...universeEsLintConfig.rules,
    'no-unused-expressions': 'off',
    'babel/no-unused-expressions': 'error',
    'react/prop-types': 'off',
    'react/no-unknown-property': [2, { ignore: ['class', 'for'] }],
    'react/jsx-filename-extension': ['error', { extensions: ['.js', '.tsx'] }],
    '@typescript-eslint/explicit-module-boundary-types': 'off',
    'react/react-in-jsx-scope': 'off',
    'import/no-unresolved': 'off',
  },
  overrides: [
    {
      files: ['**/*.ts?(x)'],
      parser: '@typescript-eslint/parser',
      parserOptions: {
        ecmaVersion: 2015,
        sourceType: 'module',
        project: './web/tsconfig.json',
      },
      plugins: ['@typescript-eslint'],
      rules: {
        'default-param-last': 'off',
        '@typescript-eslint/default-param-last': 'off',
        'no-empty-function': 'off',
        '@typescript-eslint/no-empty-function': 'off',
        'no-magic-numbers': 'off',
        '@typescript-eslint/no-magic-numbers': 'off',
        'no-return-await': 'off',
        '@typescript-eslint/return-await': 'error',
        'no-unused-expressions': 'off',
        'babel/no-unused-expressions': 'off',
        '@typescript-eslint/no-unused-expressions': 'error',
        'no-unused-vars': 'off',
        'no-useless-constructor': 'off',
        '@typescript-eslint/no-useless-constructor': 'error',
        'no-dupe-class-members': 'off',
        '@typescript-eslint/no-dupe-class-members': 'error',
        '@typescript-eslint/no-unused-vars': [
          'error',
          {
            argsIgnorePattern: '^_',
            varsIgnorePattern: '^ignored',
            args: 'after-used',
            ignoreRestSiblings: true,
          },
        ],
        'require-await': 'off',
        '@typescript-eslint/require-await': 'error',
        'babel/camelcase': 'off',
        '@typescript-eslint/naming-convention': [
          'error',
          {
            selector: 'variable',
            types: ['boolean'],
            format: ['PascalCase'],
            prefix: ['is', 'should', 'has', 'can', 'did', 'will'],
            leadingUnderscore: 'allow',
          },
        ],
        'no-use-before-define': 'off',
        '@typescript-eslint/no-use-before-define': ['error', 'nofunc'],
        'no-array-constructor': 'off',
        '@typescript-eslint/no-array-constructor': 'error',
        'react/prop-types': 'off',
      },
      extends: [
        'plugin:@typescript-eslint/recommended',
        'plugin:@typescript-eslint/eslint-recommended',
        'prettier/@typescript-eslint',
      ],
    },
    {
      files: ['**/__tests__/**/*.tsx', '*.test.js'],
      settings: {
        'import/resolver': {
          jest: {
            jestConfigFile: './jest.config',
          },
        },
      },
    },
  ],
  settings: {
    'import/resolver': {
      node: {
        extensions: ['.tsx', '.ts', '.js', '.web.js', '.desktop.js', '.mobile.js'],
        moduleDirectory: ['node_modules', 'js/'],
      },
    },
  },
};
