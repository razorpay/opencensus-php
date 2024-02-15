const path = require('path');

module.exports = {
  extends: [
    'kentcdodds',
    'kentcdodds/react',
    'plugin:prettier/recommended',
    'plugin:json/recommended-with-comments',
    'plugin:yml/standard',
  ],
  plugins: ['no-relative-import-paths', 'i18n-rules'],
  ignorePatterns: ['.eslintrc.js'],
  rules: {
    'no-shadow': 'off',
    'babel/camelcase': 'off',
    'import/no-extraneous-dependencies': 'off',
    'max-lines-per-function': 'off',
    'max-statements': 'off',
    'no-console': 'off',
    'no-negated-condition': 'off',
    'json/*': [
      'error',
      {
        allowComments: true,
      },
    ],
    complexity: 'off',
    'no-async-promise-executor': 'warn',
    'yml/sort-keys': 'off',
    'no-unused-expressions': 'off',
    'babel/no-unused-expressions': ['error', { allowShortCircuit: true, allowTernary: true }],
    'react/prop-types': 'off',
    'react/no-unknown-property': [2, { ignore: ['class', 'for'] }],
    'react/jsx-filename-extension': ['error', { extensions: ['.js', '.tsx'] }],
    '@typescript-eslint/explicit-module-boundary-types': 'off',
    'react/react-in-jsx-scope': 'off',
    'import/no-unresolved': 'off',
    'import/order': [
      'warn',
      {
        groups: [['builtin', 'external'], 'internal', ['parent', 'sibling'], 'type'],
        pathGroups: [{ group: 'builtin', pattern: 'react', position: 'before' }],
        pathGroupsExcludedImportTypes: ['builtin'],
        distinctGroup: false,
        'newlines-between': 'always',
        alphabetize: { order: 'asc', caseInsensitive: false },
      },
    ],
    'react/display-name': 'off',
    'react/no-find-dom-node': 'warn',
    'no-relative-import-paths/no-relative-import-paths': [
      'error',
      { allowSameFolder: true, rootDir: 'web/js' },
    ],
    'no-restricted-imports': [
      // todo: Change this to error when we modify Validate lint CI to run on only changed files
      'warn',
      {
        paths: [
          {
            name: 'common/ui/HeaderAction',
            message:
              'This component is deprecated. Please use ProductWrapper instead. Refer https://docs.google.com/document/d/1eTH_ZGSeHlgnMhAgTwf5S0eNpjQIpPH-z_RzYJo78aY/edit?usp=sharing',
          },
          {
            name: 'common/deprecated/withRouter',
            message: 'withRouter is not supported in React Router v6, please use hooks instead.',
          },
        ],
      },
    ],
    'i18n-rules/no-region-specific-keyword': 'warn',
    'i18n-rules/no-currency-hardcoding': 'warn',
    'i18n-rules/no-href-hardcoding': 'warn',
    'i18n-rules/no-region-specific-image': 'warn',
  },
  env: {
    browser: true,
    node: true,
    jest: true,
  },
  globals: {
    __STAGE__: false,
    __VERSION__: false,
    __CONFIG__: false,
    __APP_NAME__: false,
    __webpack_public_path__: true,
    d3: 'readonly',
  },
  overrides: [
    {
      files: ['*.js', '*.jsx'],
      parser: '@babel/eslint-parser',
      parserOptions: {
        babelOptions: {
          configFile: path.resolve(__dirname, './web/.babelrc.js'),
        },
      },
    },
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
        'one-var': 'off',
        'object-property-newline': 0,
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
        // Restricting custom components added in reports from being used elsewhere
        'import/no-restricted-paths': [
          'error',
          {
            basePath: './web/js/',
            zones: [
              {
                target: './',
                from: './merchant_common/views/Reports/components/',
                message: 'These components are restricted to reports. Please avoid using it.',
              },
            ],
          },
        ],
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
    // Allowing custom components to be used in reports dir
    {
      files: ['./web/js/merchant_common/views/Reports/**', ''],
      rules: {
        'import/no-restricted-paths': 'off',
      },
    },
    {
      files: ['./web/**/__test__/**/*.+(js|ts|tsx|jsx)', './web/**/*.test.*'],
      rules: {
        'i18n-rules/no-region-specific-keyword': 'off',
        'i18n-rules/no-currency-hardcoding': 'off',
        'i18n-rules/no-href-hardcoding': 'off',
        'i18n-rules/no-region-specific-image': 'off',
      },
    },
  ],
  settings: {
    'import/resolver': {
      node: {
        extensions: ['.tsx', '.ts', '.js', '.web.js', '.desktop.js', '.mobile.js'],
        moduleDirectory: ['node_modules', 'js/'],
      },
      alias: {
        map: [['assets', './web/css/assets']],
        extensions: ['.js'],
      },
    },
  },
};
