import { formatRule } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/util/formatRule';

import type {
  Rule,
  ShopifyRule,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/types';

describe('formatRule', () => {
  it('should format a Rule to ShopifyRule using Shopify formatter', () => {
    const rule: Rule = {
      condition: {
        id: 'root',
        path: [],
        combinator: 'or',
        conditions: [
          {
            fact: 'discount',
            operator: 'lt',
            value: 20,
            id: 'id2',
            path: [0],
          },
        ],
      },
      actions: [{ type: 'HIDE_COD' }],
    };

    const expectedShopifyRule: ShopifyRule = {
      rules: [
        {
          conditions: {
            any: [{ fact: 'discount', op: 'lt', val: 20 }],
          },
          actions: [{ type: 'HIDE_COD' }],
        },
      ],
      ruleFacts: {},
    };

    const result = formatRule(rule);
    expect(result).toEqual(expectedShopifyRule);
  });

  it('should return an empty ShopifyRule if shopifyFormatter returns an empty rule', () => {
    const rule: Rule = {
      condition: {
        id: 'root',
        path: [],
        combinator: 'and',
        conditions: [],
      },
      actions: [{ type: 'HIDE_COD' }],
    };

    const expectedShopifyRule: ShopifyRule = {
      rules: [
        {
          conditions: {
            all: [],
          },
          actions: [{ type: 'HIDE_COD' }],
        },
      ],
      ruleFacts: {},
    };

    const result = formatRule(rule);
    expect(result).toEqual(expectedShopifyRule);
  });

  it('should handle complex nested conditions in Rule', () => {
    const rule: Rule = {
      condition: {
        id: 'root',
        path: [],
        combinator: 'and',
        conditions: [
          { fact: 'discount', operator: 'gt', value: 18, id: 'id1', path: [0] },
          {
            id: 'id2',
            path: [1],
            combinator: 'or',
            conditions: [
              {
                fact: 'subtotal',
                operator: 'eq',
                value: 300,
                id: 'id3',
                path: [1, 0],
              },
              {
                fact: 'customerEmail',
                operator: 'in',
                value: 'admin@store.com',
                id: 'id4',
                path: [1, 1],
              },
            ],
          },
        ],
      },
      actions: [{ type: 'HIDE_COD' }],
    };

    const expectedShopifyRule: ShopifyRule = {
      rules: [
        {
          conditions: {
            all: [
              { fact: 'discount', op: 'gt', val: 18 },
              {
                any: [
                  { fact: 'subtotal', op: 'eq', val: 300 },
                  { fact: 'customerEmail', op: 'in', val: 'admin@store.com' },
                ],
              },
            ],
          },
          actions: [{ type: 'HIDE_COD' }],
        },
      ],
      ruleFacts: {},
    };

    const result = formatRule(rule);
    expect(result).toEqual(expectedShopifyRule);
  });
});
