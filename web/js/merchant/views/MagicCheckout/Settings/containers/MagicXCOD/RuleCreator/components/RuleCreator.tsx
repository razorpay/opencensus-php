import * as React from 'react';

import { RuleCreatorContext } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/context';
import { createRCInternalStateHook } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/state';
import { createDefaultRule } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/state/defaults';
import { parseFacts } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/util/rulefacts/parse';

import type {
  Fact,
  RuleCreatorContextType,
  Rule,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/types';

export const useRCInternalState: any = createRCInternalStateHook();
const RuleCreatorProvider = RuleCreatorContext.Provider;

type RuleCreatorInternalProps = RuleCreatorContextType & {
  children: React.ReactNode;
};
const RuleCreatorInternal: React.FC<RuleCreatorInternalProps> = (props) => {
  const { children, ...ruleCreatorProps } = props;
  const setState = useRCInternalState((state) => state.setState);

  React.useEffect(() => {
    setState({
      rule: props.defaultRule,
      ruleFacts: parseFacts(props.facts),
      validator: props.validator,
    });
  }, []);

  return <RuleCreatorProvider value={ruleCreatorProps}>{children}</RuleCreatorProvider>;
};

export type RuleCreatorProps = {
  defaultRule?: Rule;
  facts?: Array<Fact>;
  children?: React.ReactNode;
  validator?: (rule: Rule) => Record<string, any | never>;
  [prop: string]: any;
};
export const RuleCreator: React.FC<RuleCreatorProps> = ({
  defaultRule,
  children,
  ...restProps
}) => {
  const initialRule = defaultRule || createDefaultRule();

  return (
    <RuleCreatorInternal defaultRule={initialRule} {...restProps}>
      {children}
    </RuleCreatorInternal>
  );
};
