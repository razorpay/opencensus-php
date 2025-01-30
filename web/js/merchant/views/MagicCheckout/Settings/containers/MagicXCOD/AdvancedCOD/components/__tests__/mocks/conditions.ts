import type { Rule } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/types';

export const merchantId = 'm001';

export const emptyRule: Rule = {
  condition: {
    id: 'root',
    path: [],
    combinator: 'and',
    conditions: [
      {
        id: 'group1',
        path: [0],
        combinator: 'and',
        conditions: [
          {
            id: 'condition1',
            path: [0, 0],
            fact: '',
            operator: 'eq',
            value: '',
          },
        ],
      },
    ],
  },
  actions: [{ type: '', params: { value: [] } }],
};

export const emptyRuleWithTwoConditions = {
  condition: {
    id: 'root',
    path: [],
    combinator: 'and',
    conditions: [
      {
        id: 'group1',
        path: [0],
        combinator: 'and',
        conditions: [
          {
            id: 'condition1',
            path: [0, 0],
            fact: '',
            operator: 'eq',
            value: '',
          },
          {
            id: 'condition2',
            path: [0, 1],
            fact: '',
            operator: 'eq',
            value: '',
          },
        ],
      },
    ],
  },
  actions: [{ type: '', params: { value: '' } }],
};

export const emptyRuleWithTwoConditionGroups = {
  condition: {
    id: 'root',
    path: [],
    combinator: 'and',
    conditions: [
      {
        id: 'group1',
        path: [0],
        combinator: 'and',
        conditions: [
          {
            id: 'condition1',
            path: [0, 0],
            fact: '',
            operator: 'eq',
            value: '',
          },
          {
            id: 'condition2',
            path: [0, 1],
            fact: '',
            operator: 'eq',
            value: '',
          },
        ],
      },
      {
        id: 'group2',
        path: [1],
        combinator: 'and',
        conditions: [
          {
            id: 'condition3',
            path: [1, 0],
            fact: '',
            operator: 'eq',
            value: '',
          },
        ],
      },
    ],
  },
  actions: [{ type: '', params: { value: '' } }],
};
