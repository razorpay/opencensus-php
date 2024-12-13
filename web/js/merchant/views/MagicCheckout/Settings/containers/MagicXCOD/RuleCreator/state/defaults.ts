import { generateId } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/util/id';

import type {
  Action,
  ConditionGroup,
  Path,
  Rule,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/types';

export const createDefaultRule = (options: { defaultAction?: Action } = {}): Rule => ({
  condition: {
    id: 'root',
    path: [],
    combinator: 'and',
    conditions: [
      {
        id: generateId(),
        path: [0],
        combinator: 'and',
        conditions: [
          {
            id: generateId(),
            path: [0, 0],
            fact: '',
            operator: 'eq',
            value: '',
          },
        ],
      },
    ],
  },
  actions: options.defaultAction ? [options.defaultAction] : [{ type: '', params: { value: '' } }],
});

export const createDefaultConditionGroup = (path: Path): ConditionGroup => ({
  path,
  combinator: 'and',
  conditions: [
    {
      path: [...path, 0],
      fact: '',
      operator: 'eq',
      value: '',
    },
  ],
});
