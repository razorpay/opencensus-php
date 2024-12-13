import { useRCInternalState } from './components/RuleCreator';
import { byteLength, getUsagePercentage } from './size';
import * as actionMutations from './util/actions/mutations';
import * as cgMutations from './util/conditions/mutations';
import { formatRule } from './util/formatRule';
import { toString } from './util/json';

import type { Rule, Action, ConditionOrGroup, Path } from './types';

export const useRule = () => {
  const { rule, ruleSize } = useRCInternalState();
  return { rule, ruleSize };
};

export const useRuleFacts = () => {
  const { ruleFacts } = useRCInternalState();
  return { ruleFacts };
};

export const useRuleValidation = () => {
  const { rule, validationResult, validator, setState } = useRCInternalState();

  const validate = () => {
    const errors = validator(rule);
    const isValid =
      Object.keys(errors.conditions).length === 0 && Object.keys(errors.actions).length === 0;
    setState({ validationResult: errors });

    return isValid;
  };

  return { validationResult, validate };
};

export const useRuleMutations = () => {
  const { rule, setRule, setState } = useRCInternalState();

  const setRuleWithMeta = (rule: Rule) => {
    const size = byteLength(toString(formatRule(rule)));
    const usage = getUsagePercentage(size, '40kb'); // hardcoding limit for now

    setState({
      rule,
      ruleSize: {
        usage,
        value: size,
      },
    });
  };

  const mutations = {
    conditions: {
      add: (conditionOrGroup: ConditionOrGroup, parentPath: Path) =>
        setRuleWithMeta(cgMutations.add(rule, conditionOrGroup, parentPath)),
      remove: (path: Path) => setRuleWithMeta(cgMutations.remove(rule, path)),
      update: (prop: string, value: any, path: Path) =>
        setRuleWithMeta(cgMutations.update(rule, prop, value, path)),
    },
    actions: {
      add: (action: Action) => setRule(actionMutations.add(rule, action)),
      update: (prop: any, value: any, index?: number) =>
        setRule(actionMutations.update(rule, prop, value, index)),
    },
  };

  return mutations;
};
