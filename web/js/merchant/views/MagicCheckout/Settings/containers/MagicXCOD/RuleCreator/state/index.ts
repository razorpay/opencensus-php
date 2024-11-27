import create from 'zustand';

import type { Rule } from '../types';

type RCInternalState = {
  rule: Rule;
  setRule: (rule: Rule) => void;
};

const defaultInitialRule: Rule = {
  condition: {
    id: 'root',
    path: [],
    combinator: 'and',
    conditions: [],
  },
  actions: [],
};

export const createDefaultRule = () => defaultInitialRule;

export const createRCInternalStateHook = (
  defaultInitialState = {
    rule: defaultInitialRule,
  },
) => {
  const useRCInternalState = create<RCInternalState>((set) => ({
    ...defaultInitialState,
    setRule: (rule: Rule) => set({ rule }),
  }));
  return useRCInternalState;
};
