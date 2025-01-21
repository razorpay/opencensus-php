import React from 'react';

// facts
export type Fact = {
  name: string;
  label: string;
  type: 'number' | 'string' | 'boolean';
  operators: Array<Operator>;
  defaultOperator: Operator;
  validator: any;
  // optionals
  valueEditorType?: string;
  values?: OptionList;
  defaultValue?: any;
  placeholder?: string;
};

export type PropFact = Pick<Fact, 'name' | 'label' | 'type'> &
  Partial<Omit<Fact, 'name' | 'label' | 'type'>>;

// options
export type Option = {
  name: string;
  label: string;
  [key: string]: any;
};

export type OptionGroup = {
  label: string;
  options: Array<Option>;
};

export type OptionList = Array<Option> | Array<OptionGroup>;

// operators
export type OperatorValue = 'eq' | 'lt' | 'le' | 'gt' | 'ge' | 'in';
export type Operator = {
  [K in OperatorValue]: { name: K; value: K; label: string };
}[OperatorValue];

// condition and condition groups
export type Path = number[];

export type Combinator = 'and' | 'or';
export type Condition = {
  path: Path;
  id: string;
  fact: string;
} & (
  | { operator: 'lt' | 'le' | 'gt' | 'ge'; value: number }
  | { operator: 'in'; value: string }
  | { operator: 'eq'; value: number | boolean | string }
);
export type ConditionWithoutIds = Omit<Condition, 'id' | 'path'> &
  Partial<Pick<Condition, 'id' | 'path'>>;

export type ConditionGroup = {
  path: Path;
  id: string;
  combinator: Combinator;
  conditions: Array<Condition | ConditionGroup>;
};
export type ConditionGroupWithoutIds = {
  path?: Path;
  id?: string;
  combinator: Combinator;
  conditions: Array<ConditionWithoutIds | ConditionGroupWithoutIds>;
};

export type ConditionOrGroup = Condition | ConditionGroup;
export type ConditionOrGroupWithoutIds = ConditionWithoutIds | ConditionGroupWithoutIds;

export type MutableConditionOrGroupProps = 'combinator' | 'fact' | 'operator' | 'value';

// actions
export type Action<
  T extends string = string,
  P extends Record<string, unknown> = Record<'value', any>,
> = {
  type: T;
  params?: P;
};

export type MutableActionProps = 'type' | 'params';

// rules
export type Rule<Type extends 'partial' | undefined = undefined> = {
  condition: Type extends 'partial' ? ConditionGroupWithoutIds : ConditionGroup;
  actions: Array<Action>;
};

export type RuleSize = {
  limit: number;
  size: number;
  usage: number;
};

export type RuleSizeInKb = number | `${number}kb`;

// validation
export type ConditionValidation = Partial<{
  fact: string | undefined;
  operator: string | undefined;
  value: string | undefined;
}>;

export type ConditionGroupValidation = {
  [conditionID: string]: ConditionValidation;
};

export type RuleValidationResult = {
  conditions: {
    [id: string]: ConditionGroupValidation;
  };
  actions: {
    [idx: string]: {
      type: string;
      params: string;
    };
  };
};

export type RuleValidator = (rule: Rule) => RuleValidationResult;

// SR = ShopifyRule
export type SRCombinator = 'all' | 'any';
export type SRCondition = {
  fact: string;
} & (
  | { op: 'lt' | 'le' | 'gt' | 'ge'; val: number }
  | { op: 'in'; val: string }
  | { op: 'eq'; val: number | boolean | string }
);
export type SRConditionGroup = Partial<{
  [K in SRCombinator]: Array<SRConditionOrGroup>;
}>;
export type SRConditionOrGroup = SRCondition | SRConditionGroup;
export type ShopifyRule = {
  rules: Array<{
    conditions: SRConditionGroup;
    actions: Array<Action>;
  }>;
  ruleFacts: Record<string, never>;
};

// react components and context
export type RCEngineProps = {
  facts: PropFact[];
  sizeLimit: RuleSizeInKb;
  defaultRule?: Rule;
  validator: RuleValidator;
  children: React.ReactNode;
};

type ValidateRuleFunction = (rule: Rule) => boolean;

export type RCEngineContext = {
  rule: Rule;
  facts: Fact[];
  ruleSize: RuleSize;
  validationResult: RuleValidationResult;
  validateRule: ValidateRuleFunction;
  validator: (rule: Rule) => RuleValidationResult;
  ruleMutations: {
    addCondition: (conditionOrGroup: ConditionOrGroupWithoutIds, parentPath: Path) => void;
    updateCondition: (prop: MutableConditionOrGroupProps, value: any, path: Path) => void;
    removeCondition: (path: Path) => void;
    addAction: (action: Action) => void;
    updateAction: (prop: 'type' | 'params', value: any, index: number) => void;
    removeAction: (index: number) => void;
  };
};

// component/context utils
export type UseRuleInternal = (props: RCEngineProps) => [
  {
    rule: Rule;
    size: RuleSize;
  },
  RCEngineContext['ruleMutations'],
];

export type UseFactsInternal = (props: RCEngineProps) => Fact[];

export type UseValidationInternal = (
  props: RCEngineProps,
) => [RuleValidationResult, ValidateRuleFunction];
