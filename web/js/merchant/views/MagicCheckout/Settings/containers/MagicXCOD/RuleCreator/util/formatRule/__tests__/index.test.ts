import { formatRule } from '../index';
import { formatter as shopifyFormatter } from '../shopify';

import type { Rule, ShopifyRule } from '../../../types';

// jest.mock('../shopify');

describe('formatRule', () => {
  // const mockShopifyFormatter = shopifyFormatter as jest.MockedFunction<typeof shopifyFormatter>;

  // beforeEach(() => {
  //   mockShopifyFormatter.mockClear();
  // });

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
      condition: {
        any: [{ fact: 'discount', op: 'lt', val: 20 }],
      },
      actions: [{ type: 'HIDE_COD' }],
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
      condition: {
        all: [],
      },
      actions: [{ type: 'HIDE_COD' }],
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
                fact: 'location',
                operator: 'eq',
                value: 'BLR',
                id: 'id3',
                path: [1, 0],
              },
              {
                fact: 'subscription',
                operator: 'eq',
                value: 'premium',
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
      condition: {
        all: [
          { fact: 'discount', op: 'gt', val: 18 },
          {
            any: [
              { fact: 'location', op: 'eq', val: 'BLR' },
              { fact: 'subscription', op: 'eq', val: 'premium' },
            ],
          },
        ],
      },
      actions: [{ type: 'HIDE_COD' }],
    };

    const result = formatRule(rule);
    expect(result).toEqual(expectedShopifyRule);
  });
});
