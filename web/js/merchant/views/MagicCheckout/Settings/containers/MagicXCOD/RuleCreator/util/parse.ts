import { generateId } from './id';

import type {
  Rule,
  ShopifyRule,
  SRConditionGroup,
  Condition,
  ConditionGroup,
  Path,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/types';

const parseSRConditionGroup = (cg: SRConditionGroup, path: Path = []): Rule['condition'] => {
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
  const rule = shopifyRule.rules[0];
  const actions = rule.actions;
  const condition = rule.conditions;
  const ruleCondition = parseSRConditionGroup(condition);

  return {
    actions,
    condition: ruleCondition,
  };
};
