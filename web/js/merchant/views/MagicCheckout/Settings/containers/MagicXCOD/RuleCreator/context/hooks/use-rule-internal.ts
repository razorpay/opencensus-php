import { useMemo, useState } from 'react';

import {
  getRuleFromProps,
  getRuleSize,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/context/utils';
import * as actionMutations from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/util/actions/mutations';
import * as cgMutations from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/util/conditions/mutations';

import type {
  RCEngineContext,
  Rule,
  RuleSize,
  UseRuleInternal,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/types';

export const useRuleInternal: UseRuleInternal = (props) => {
  const ruleFromProps = getRuleFromProps(props);
  const [condition, setCondition] = useState<Rule['condition']>(ruleFromProps.condition);
  const [actions, setActions] = useState<Rule['actions']>(ruleFromProps.actions);

  const ruleSizeLimit = useMemo(
    () => parseFloat(props.sizeLimit.toString().toLowerCase().replace('kb', '')),
    [props.sizeLimit],
  );

  const size: RuleSize = useMemo(
    () => getRuleSize({ condition, actions }, ruleSizeLimit),
    [condition, actions, ruleSizeLimit],
  );

  const mutations: RCEngineContext['ruleMutations'] = useMemo(() => {
    const rule = { condition, actions };

    return {
      addCondition: (conditionOrGroup, parentPath) => {
        setCondition(cgMutations.add(rule, conditionOrGroup, parentPath).condition);
      },
      updateCondition: (prop, value, path) => {
        setCondition(cgMutations.update(rule, prop, value, path).condition);
      },
      removeCondition: (parentPath) => {
        setCondition(cgMutations.remove(rule, parentPath).condition);
      },
      addAction: (action) => {
        setActions(actionMutations.add(rule, action).actions);
      },
      updateAction: (prop, value, index) => {
        setActions(actionMutations.update(rule, prop, value, index).actions);
      },
      removeAction: (index) => {
        setActions(actionMutations.remove(rule, index).actions);
      },
    };
  }, [condition, actions]);

  return [
    {
      rule: { condition, actions },
      size,
    },
    mutations,
  ];
};
