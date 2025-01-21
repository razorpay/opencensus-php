import { useContext, useState } from 'react';

import { RCEngineContext } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/context/RuleCreatorContext';
import {
  RCEngineContext as RCEngineContextType,
  RuleValidationResult,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/types';

function assertContext(
  ctx: RCEngineContextType | null,
  hook: string,
): asserts ctx is RCEngineContextType {
  if (!ctx) {
    throw new Error(`Please use ${hook} within RuleCreatorEngine.`);
  }
}

export const useRule = () => {
  const ctx = useContext(RCEngineContext);

  assertContext(ctx, 'useRule');

  return {
    rule: ctx.rule,
    ruleSize: ctx.ruleSize,
  };
};

export const useRuleFacts = () => {
  const ctx = useContext(RCEngineContext);

  assertContext(ctx, 'useRuleFacts');

  return {
    ruleFacts: ctx.facts,
  };
};

export const useRuleValidation = () => {
  const ctx = useContext(RCEngineContext);

  assertContext(ctx, 'useRuleValidation');

  const { rule, validationResult, validateRule } = ctx;
  const validate = () => validateRule(rule);

  return { validationResult, validate };
};

export const useRuleMutations = () => {
  const ctx = useContext(RCEngineContext);

  assertContext(ctx, 'useRuleMutations');

  const ruleMutation = ctx.ruleMutations;
  const mutations = {
    conditions: {
      add: ruleMutation.addCondition,
      update: ruleMutation.updateCondition,
      remove: ruleMutation.removeCondition,
    },
    actions: {
      add: ruleMutation.addAction,
      update: ruleMutation.updateAction,
      remove: ruleMutation.removeAction,
    },
  };

  return mutations;
};
