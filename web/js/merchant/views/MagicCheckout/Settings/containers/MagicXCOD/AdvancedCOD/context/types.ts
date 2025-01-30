import React from 'react';

import type {
  Rule as ACODRule,
  RuleType as ACODRuleType,
} from 'merchant/reducers/magicCheckout/magicxACODRules/types';
import type { Rule } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/types';

export type ActiveACODRule = (Omit<ACODRule, 'id'> & { id?: string }) | undefined;
export type AdvancedCODContext = {
  isModalOpen: boolean;
  setIsModalOpen: (isModalOpen: boolean) => void;
  isProcessingRequest: boolean;
  setIsProcessingRequest: (isProcessingRequest: boolean) => void;
  activeACODRule: ActiveACODRule;
  merchantId: string;
  acodRule?: ACODRule;
  defaultRCERule: Rule;
  resetActiveACODRule: () => void;
  storeActions: {
    addRule: (acodRule: ACODRule) => void;
    updateRule: (acodRule: ACODRule) => void;
    removeRule: (acodRule: ACODRule) => void;
  };
  formActions: {
    createRule: (rule: Partial<ACODRule>) => void;
    updateRule: (rule: ACODRule) => void;
  };
  updateActiveACODRule: (rule: Partial<ActiveACODRule>) => void;
  openModalWithActiveRule: (type: ACODRuleType, rule?: ActiveACODRule) => void;
  notify: (type: 'success' | 'error' | 'neutral', message: string) => void;
};

export type ACODProviderProps = {
  notify: AdvancedCODContext['notify'];
  merchantId: string;
  storeActions: AdvancedCODContext['storeActions'];
  children: React.ReactNode;
};

// hooks
export type UseFormActions = (args: {
  notify: AdvancedCODContext['notify'];
  setIsProcessingRequest: (isProcessingReq: boolean) => void;
  setIsModalOpen: (isModalOpen: boolean) => void;
  storeActions: AdvancedCODContext['storeActions'];
  merchantId: string;
}) => {
  createRule: (rule: Partial<ACODRule>) => Promise<void>;
  updateRule: (rule: ACODRule) => Promise<void>;
};
