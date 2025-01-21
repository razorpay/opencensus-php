import {
  byteLength,
  getUsagePercentage,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/size';
import { createDefaultRule } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/state/defaults';
import { formatRule } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/util/formatRule';
import { toString } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/util/json';
import { parseFacts } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/util/rulefacts/parse';

import type {
  Fact,
  RCEngineProps,
  Rule,
  RuleSize,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/types';

export const getRuleFromProps = (props: RCEngineProps): Rule => {
  const { defaultRule } = props;

  if (!defaultRule) {
    // this default rule is created for the CURRENT USE CASE ONLY
    // ie. the condition contains ConditionGroup(s) which in turn
    // contain Condition(s)
    return createDefaultRule();
  }

  return defaultRule;
};

export const getFactsFromProps = (props: RCEngineProps): Fact[] => {
  return parseFacts(props.facts);
};

export const getRuleSize = (rule: Rule, limit: number): RuleSize => {
  const formattedRule = formatRule(rule);
  const bytesize = byteLength(toString(formattedRule));
  const usage = getUsagePercentage(bytesize, limit);
  return {
    limit,
    usage,
    size: bytesize,
  };
};
