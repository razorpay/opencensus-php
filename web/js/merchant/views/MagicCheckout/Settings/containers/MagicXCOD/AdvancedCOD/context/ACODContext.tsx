import React, { createContext, useState, useMemo, useContext } from 'react';

import { useFormActions } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/context/hooks/use-form-actions';
import { createNewRuleState } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/util';
import { toJSON } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/util/json';
import { parseShopifyRule } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/util/parse';

import type { RuleType as ACODRuleType } from 'merchant/reducers/magicCheckout/magicxACODRules/types';
import type {
  AdvancedCODContext,
  ACODProviderProps,
  ActiveACODRule,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/context/types';

export const ACODContext = createContext<AdvancedCODContext | null>(null);

export const useACODContext = () => useContext(ACODContext);

export function assertACODContext(
  ctx: AdvancedCODContext | null,
): asserts ctx is AdvancedCODContext {
  if (!ctx) {
    throw new Error('Use `ACODContext` only from within `ACODProvider`.');
  }
}

export const ACODProvider: React.FC<ACODProviderProps> = (props) => {
  const { children, ...restProps } = props;
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [isProcessingRequest, setIsProcessingRequest] = useState(false);
  const [activeACODRule, setActiveACODRule] = useState<
    AdvancedCODContext['activeACODRule'] | undefined
  >(undefined);
  const formActions = useFormActions({
    setIsModalOpen,
    setIsProcessingRequest,
    merchantId: restProps.merchantId,
    notify: restProps.notify,
    storeActions: restProps.storeActions,
  });

  const defaultRCERule = useMemo(
    () => parseShopifyRule(toJSON(activeACODRule?.rule || '')),
    [activeACODRule],
  );

  const openModalWithActiveRule = (
    type: ACODRuleType,
    ruleOrRuleCount?: AdvancedCODContext['activeACODRule'] | number,
  ) => {
    if (ruleOrRuleCount && typeof ruleOrRuleCount !== 'number') {
      setActiveACODRule(ruleOrRuleCount);
    } else {
      const rulesCount = ruleOrRuleCount as number;
      setActiveACODRule(
        createNewRuleState({ rulesCount, type, merchant_id: restProps.merchantId }),
      );
    }

    setIsModalOpen(true);
  };

  const updateActiveACODRule = (rule: Partial<ActiveACODRule>) => {
    setActiveACODRule((prevRule) =>
      prevRule ? { ...prevRule, ...rule } : (rule as ActiveACODRule),
    );
  };

  const resetActiveACODRule = () => {
    setActiveACODRule(undefined);
  };

  return (
    <ACODContext.Provider
      value={{
        isModalOpen,
        setIsModalOpen,
        isProcessingRequest,
        setIsProcessingRequest,
        activeACODRule,
        defaultRCERule,
        resetActiveACODRule,
        formActions,
        updateActiveACODRule,
        openModalWithActiveRule,
        ...restProps,
      }}
    >
      {children}
    </ACODContext.Provider>
  );
};
