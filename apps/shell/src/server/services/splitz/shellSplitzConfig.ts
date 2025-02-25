import type { VariantConfigArgs } from './types';

export const shellSplitzConfig: VariantConfigArgs[] = [
  {
    defaultVariant: {
      name: 'variables',
      variables: [
        {
          key: 'variant',
          value: 'off',
        },
      ],
    },
    experimentId: {
      stage: 'OWphI2FwA0dqSz',
      beta: 'OWphI2FwA0dqSz',
      devstack: 'OWphI2FwA0dqSz',
      production: 'OWpgdX3tJs6kZo',
      canary: 'OWpgdX3tJs6kZo',
    },
    uniqueHashKey: 'render-via-shell-client',
    evaluater: (variables) => variables?.['variant'] === 'on',
  },
];
