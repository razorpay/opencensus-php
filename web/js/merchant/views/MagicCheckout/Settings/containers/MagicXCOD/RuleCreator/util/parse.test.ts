import { parseShopifyRule } from './parse';
import { ShopifyRule } from '../types';

const idRegex = /^\w{7}$/;

describe('parseShopifyRule', () => {
  it('should parse a simple ShopifyRule with one condition into a Rule', () => {
    const shopifyRule: ShopifyRule = {
      condition: {
        all: [{ fact: 'age', op: 'gt', val: 18 }],
      },
      actions: [{ type: 'HIDE_COD' }],
    };

    const parsedRule = parseShopifyRule(shopifyRule);

    expect(parsedRule.actions).toEqual(shopifyRule.actions);
    expect(parsedRule.condition.id).toBe('root');
    expect(parsedRule.condition.path).toEqual([]);
    expect(parsedRule.condition.combinator).toBe('and');
    expect(parsedRule.condition.conditions).toHaveLength(1);

    const condition = parsedRule.condition.conditions[0];
    expect(condition).toMatchObject({
      fact: 'age',
      operator: 'gt',
      value: 18,
    });
    expect(condition.path).toEqual([0]);
    expect(condition.id).toMatch(idRegex);
  });

  it('should parse a nested ShopifyRule with multiple conditions and groups', () => {
    const shopifyRule: ShopifyRule = {
      condition: {
        all: [
          { fact: 'age', op: 'gt', val: 18 },
          {
            any: [
              { fact: 'location', op: 'eq', val: 'NY' },
              { fact: 'subscription', op: 'eq', val: 'premium' },
            ],
          },
        ],
      },
      actions: [{ type: 'HIDE_COD' }],
    };

    const parsedRule = parseShopifyRule(shopifyRule);

    expect(parsedRule.actions).toEqual(shopifyRule.actions);
    expect(parsedRule.condition.id).toBe('root');
    expect(parsedRule.condition.path).toEqual([]);
    expect(parsedRule.condition.combinator).toBe('and');
    expect(parsedRule.condition.conditions).toHaveLength(2);

    const ageCondition = parsedRule.condition.conditions[0];
    expect(ageCondition).toMatchObject({
      fact: 'age',
      operator: 'gt',
      value: 18,
      path: [0],
    });
    expect(ageCondition.id).toMatch(idRegex);

    const locationGroup = parsedRule.condition.conditions[1];
    expect(locationGroup).toMatchObject({
      combinator: 'or',
      path: [1],
    });
    expect(locationGroup.id).toMatch(idRegex);

    if ('conditions' in locationGroup) {
      const [locationCondition, subscriptionCondition] = locationGroup.conditions;
      expect(locationCondition).toMatchObject({
        fact: 'location',
        operator: 'eq',
        value: 'NY',
        path: [1, 0],
      });
      expect(locationCondition.id).toMatch(idRegex);

      expect(subscriptionCondition).toMatchObject({
        fact: 'subscription',
        operator: 'eq',
        value: 'premium',
        path: [1, 1],
      });
      expect(subscriptionCondition.id).toMatch(idRegex);
    }
  });

  it("should handle a ShopifyRule with 'any' as the root combinator", () => {
    const shopifyRule: ShopifyRule = {
      condition: {
        any: [
          { fact: 'temperature', op: 'gt', val: 30 },
          { fact: 'humidity', op: 'lt', val: 20 },
        ],
      },
      actions: [{ type: 'HIDE_COD' }],
    };

    const parsedRule = parseShopifyRule(shopifyRule);

    expect(parsedRule.actions).toEqual(shopifyRule.actions);
    expect(parsedRule.condition.id).toBe('root');
    expect(parsedRule.condition.path).toEqual([]);
    expect(parsedRule.condition.combinator).toBe('or');
    expect(parsedRule.condition.conditions).toHaveLength(2);

    const tempCondition = parsedRule.condition.conditions[0];
    expect(tempCondition).toMatchObject({
      fact: 'temperature',
      operator: 'gt',
      value: 30,
      path: [0],
    });
    expect(tempCondition.id).toMatch(idRegex);

    const humidityCondition = parsedRule.condition.conditions[1];
    expect(humidityCondition).toMatchObject({
      fact: 'humidity',
      operator: 'lt',
      value: 20,
      path: [1],
    });
    expect(humidityCondition.id).toMatch(idRegex);
  });
});
