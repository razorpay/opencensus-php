import create from 'zustand';

import { createDefaultRule } from './defaults';

import type { Fact, Rule } from '../types';

type RCInternalState = {
  rule: Rule;
  ruleFacts: Array<Fact>;
  validationResult: any;
  ruleSize: {
    value: number; // in kb
    usage: number; // percentage used
  };
  validator: (rule: Rule) => any;
  setRule: (rule: Rule) => void;
  setRuleFacts: (ruleFacts: Array<Fact>) => void;
  setState: (nextState: Partial<RCInternalState>) => void;
};

export const createRCInternalStateHook = (
  defaultInitialState = {
    rule: createDefaultRule(),
    ruleFacts: [],
    validationResult: { conditions: {}, actions: {} },
    ruleSize: { value: 0, usage: 0 },
    validator: () => ({}),
  },
) => {
  const useRCInternalState = create<RCInternalState>((set) => ({
    ...defaultInitialState,
    setRule: (rule) => set({ rule }),
    setRuleFacts: (ruleFacts) => set({ ruleFacts }),
    setState: (nextState) => set(nextState),
  }));
  return useRCInternalState;
};
