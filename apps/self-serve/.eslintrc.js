const universeEsLintConfig = require.resolve('@razorpay/universe-cli/eslintrc.typescript');

module.exports = {
  extends: [universeEsLintConfig],
  rules: {
    'object-property-newline': 0,
    'react/jsx-filename-extension': [2, { extensions: ['.js', '.jsx', '.ts', '.tsx'] }],
    'jsx-a11y/no-autofocus': [2, { ignoreNonDOM: true }],
    'no-shadow': 'off',
    '@typescript-eslint/naming-convention': 'off',
    '@typescript-eslint/ban-ts-comment': 'off',
    '@typescript-eslint/explicit-function-return-type': 'off',
    '@typescript-eslint/no-shadow': 'off',
    '@typescript-eslint/no-unused-vars': ['error', { ignoreRestSiblings: true }],
    '@typescript-eslint/ban-types': [
      'error',
      {
        extendDefaults: true,
        types: {
          '{}': false,
        },
      },
    ],
    '@typescript-eslint/restrict-plus-operands': 'off',
  },
  overrides: [
    {
      files: ['**/__tests__/**/*.tsx', '*.test.tsx'],
      settings: {
        'import/resolver': {
          jest: {
            jestConfigFile: './jest.config',
          },
        },
      },
    },
    {
      files: ['**/*.{ts,tsx}'],
      parserOptions: {
        tsconfigRootDir: __dirname,
        project: './tsconfig.eslint.json',
      },
      rules: {
        '@typescript-eslint/no-unnecessary-condition': ['off'],
        '@typescript-eslint/explicit-function-return-type': ['off'],
        '@typescript-eslint/no-shadow': ['off'],
        '@typescript-eslint/prefer-nullish-coalescing': ['warn'],
        '@typescript-eslint/no-unsafe-argument': ['warn'],
        '@typescript-eslint/prefer-optional-chain': ['warn'],
        'import/no-named-as-default': ['warn'],
        'import/namespace': ['warn'],
        '@typescript-eslint/unbound-method': ['warn'],
        '@typescript-eslint/no-dynamic-delete': ['warn'],
      },
    },
  ],
  settings: {
    'import/resolver': {
      node: {
        paths: ['src', 'static', 'e2e'],
      },
      typescript: {
        alwaysTryTypes: true,
      },
    },
  },
};
