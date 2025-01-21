import type {
  Rule,
  ShopifyRule,
  Condition,
  ConditionGroup,
  SRCondition,
  SRConditionGroup,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/types';

export const formatter: (rule: Rule) => ShopifyRule = (rule) => {
  function parseConditionGroup(group: ConditionGroup): SRConditionGroup {
    if (group.combinator === 'and') {
      return {
        all: group.conditions.map(parseConditionOrGroup),
      };
    } else {
      return {
        any: group.conditions.map(parseConditionOrGroup),
      };
    }
  }

  function parseConditionOrGroup(
    conditionOrGroup: Condition | ConditionGroup,
  ): SRCondition | SRConditionGroup {
    if ('fact' in conditionOrGroup) {
      return {
        fact: conditionOrGroup.fact,
        op: conditionOrGroup.operator as any,
        val: conditionOrGroup.value,
      };
    } else {
      return parseConditionGroup(conditionOrGroup);
    }
  }

  return {
    rules: [
      {
        conditions: parseConditionGroup(rule.condition),
        actions: rule.actions,
      },
    ],
    ruleFacts: {},
  };
};
