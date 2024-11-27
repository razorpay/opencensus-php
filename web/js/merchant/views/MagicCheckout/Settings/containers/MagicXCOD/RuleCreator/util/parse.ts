import { generateId } from './id';

import type { Rule, ShopifyRule, Condition, ConditionGroup, Path } from '../types';

const parseSRConditionGroup = (
  cg: ShopifyRule['condition'],
  path: Path = [],
): Rule['condition'] => {
  const combinator = 'all' in cg ? 'and' : 'or';
  const conditions: Array<Condition | ConditionGroup> = (
    cg[combinator === 'and' ? 'all' : 'any'] || []
  ).map((item, index) => {
    const currPath = [...path, index];
    if ('fact' in item) {
      return {
        id: generateId(),
        path: currPath,
        fact: item.fact,
        operator: item.op,
        value: item.val,
      };
    } else {
      return parseSRConditionGroup(item, currPath);
    }
  });

  return {
    id: path.length === 0 ? 'root' : generateId(),
    path,
    combinator,
    conditions,
  };
};

// Main function to parse ShopifyRule to Rule
export const parseShopifyRule = (shopifyRule: ShopifyRule): Rule => {
  const actions = shopifyRule.actions;
  const condition = shopifyRule.condition;
  const ruleCondition = parseSRConditionGroup(condition);

  return {
    actions,
    condition: ruleCondition,
  };
};
