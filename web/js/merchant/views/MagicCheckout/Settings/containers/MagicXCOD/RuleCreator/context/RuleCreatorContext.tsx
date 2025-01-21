import React, { createContext } from 'react';

import { useFactsInternal } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/context/hooks/use-facts-internal';
import { useRuleInternal } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/context/hooks/use-rule-internal';
import { useValidationInternal } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/context/hooks/use-validation-internal';

import type {
  RCEngineContext,
  RCEngineProps,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/types';

const RuleCreatorEngine = createContext<RCEngineContext | null>(null);

export const RCEngineProvider: React.FC<RCEngineProps> = (props) => {
  const { children, validator } = props;

  const [ruleState, ruleMutations] = useRuleInternal(props);
  const [validationResult, validateRule] = useValidationInternal(props);
  const ruleFacts = useFactsInternal(props);

  return (
    <RuleCreatorEngine.Provider
      value={{
        validator,
        validationResult,
        validateRule,
        ruleMutations,
        facts: ruleFacts,
        rule: ruleState.rule,
        ruleSize: ruleState.size,
      }}
    >
      {children}
    </RuleCreatorEngine.Provider>
  );
};

const RCEngineConsumer = RuleCreatorEngine.Consumer;
export { RCEngineConsumer, RuleCreatorEngine as RCEngineContext };
