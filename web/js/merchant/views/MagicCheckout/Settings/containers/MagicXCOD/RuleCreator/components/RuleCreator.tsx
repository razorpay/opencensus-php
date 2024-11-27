import * as React from 'react';

import { RuleCreatorContext } from '../context';
import { createDefaultRule, createRCInternalStateHook } from '../state';
import * as cgMutations from '../util/conditions/mutations';

import type { Fact, RuleCreatorContextType, Rule, ConditionOrGroup, Path } from '../types';

const useRCInternalState = createRCInternalStateHook();
const RuleCreatorProvider = RuleCreatorContext.Provider;

// hooks
export const useRule = () => {
  const { rule, setRule } = useRCInternalState();

  const actions = {
    conditions: {
      add: (conditionOrGroup: ConditionOrGroup, parentPath: Path) =>
        setRule(cgMutations.add(rule, conditionOrGroup, parentPath)),
      remove: (path: Path) => setRule(cgMutations.remove(rule, path)),
      update: (prop: string, value: any, path: Path) =>
        setRule(cgMutations.update(rule, prop, value, path)),
    },
  };

  return { rule, actions };
};

type RuleCreatorInternalProps = RuleCreatorContextType & {
  children: React.ReactNode;
};
const RuleCreatorInternal: React.FC<RuleCreatorInternalProps> = (props) => {
  const { children, ...ruleCreatorProps } = props;
  const setRule = useRCInternalState((state) => state.setRule);

  React.useEffect(() => {
    setRule(props.defaultRule);
  }, []);

  return <RuleCreatorProvider value={ruleCreatorProps}>{children}</RuleCreatorProvider>;
};

export type RuleCreatorProps = {
  defaultRule?: Rule;
  facts?: Array<Fact>;
  children?: React.ReactNode;
  [prop: string]: any;
};
export const RuleCreator: React.FC<RuleCreatorProps> = ({
  defaultRule,
  children,
  ...restProps
}) => {
  const initialRule = defaultRule ?? createDefaultRule();

  return (
    <RuleCreatorInternal defaultRule={initialRule} {...restProps}>
      {children}
    </RuleCreatorInternal>
  );
};
