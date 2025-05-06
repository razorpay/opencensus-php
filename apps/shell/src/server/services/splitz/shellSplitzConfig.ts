import type { VariantConfigArgs } from './types';
// .
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
    uniqueHashKey: 'one-dashboard',
    evaluater: (variables) => variables?.['variant'] === 'on',
  },
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
      stage: 'PzykDSjb5asWWk',
      beta: 'PzykDSjb5asWWk',
      devstack: 'PzykDSjb5asWWk',
      production: 'PzygPjXxh0630V',
      canary: 'PzygPjXxh0630V',
    },
    uniqueHashKey: 'one-home',
    //Check evaluation on prod as well
    evaluater: (variables) => variables?.['result'] === 'on',
  },
  {
    defaultVariant: {
      name: 'variables',
      variables: [
        {
          key: 'result',
          value: 'off',
        },
        {
          key: 'ezetapMids',
          value: '',
        },
      ],
    },
    experimentId: {
      stage: 'ODTBVSBUt60NFY',
      beta: 'ODTBVSBUt60NFY',
      devstack: 'ODTBVSBUt60NFY',
      production: 'ODTCh4BKr0owNg',
      canary: 'ODTCh4BKr0owNg',
    },
    uniqueHashKey: 'pos_sales_agent',
    evaluater: (variables) => {
      return {
        enabled: variables?.['result'] === 'on',
        ezetapMids: variables?.['ezetapMids'],
      };
    },
  },
  {
    defaultVariant: {
      name: 'variables',
      variables: [
        {
          key: 'result',
          value: 'off',
        },
      ],
    },
    experimentId: {
      stage: 'PK7igAoTxqLVkl',
      beta: 'PK7igAoTxqLVkl',
      devstack: 'PK7igAoTxqLVkl',
      production: 'PK7cRCY8lsu5ER',
      canary: 'PK7cRCY8lsu5ER',
    },
    uniqueHashKey: 'connected_navigation',
    evaluater: (variables) => variables?.['result'] === 'on',
  },
  {
    defaultVariant: {
      name: 'variables',
      variables: [
        {
          key: 'result',
          value: 'off',
        },
      ],
    },
    experimentId: {
      stage: 'OoExDczJEroNZ0',
      beta: 'OoExDczJEroNZ0',
      devstack: 'OoExDczJEroNZ0',
      production: 'OtOSbDPEDN8KFg',
      canary: 'OtOSbDPEDN8KFg',
    },
    uniqueHashKey: 'create_merchant_cta',
    evaluater: (variables) => variables?.['result'] === 'on',
  },
];
