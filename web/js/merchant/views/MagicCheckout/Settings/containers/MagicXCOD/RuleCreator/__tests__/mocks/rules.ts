import type { Rule } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/types';

export const sampleRule: Rule = {
  condition: {
    id: 'root',
    path: [],
    combinator: 'and',
    conditions: [
      {
        id: 'cond1',
        path: [0],
        fact: 'product_tag',
        operator: 'eq',
        value: 'denim',
      },
      {
        id: 'group1',
        path: [1],
        combinator: 'or',
        conditions: [
          {
            id: 'cond2',
            path: [1, 0],
            fact: 'location',
            operator: 'eq',
            value: 'BLR',
          },
          {
            id: 'cond3',
            path: [1, 1],
            fact: 'discount',
            operator: 'ge',
            value: 15,
          },
        ],
      },
    ],
  },
  actions: [{ type: 'HIDE_COD' }],
};
