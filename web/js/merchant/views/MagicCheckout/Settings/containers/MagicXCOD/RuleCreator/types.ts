// entities
export type Fact = {
  // key properties
  name: string;
  label: string;
  type: number | string | boolean;
  operators: Array<Operator>;

  // optional
  valueEditorType?: string;
  defaultOperator?: string;
  values?: OptionList;
  defaultValue?: any;
  placeholder?: string;
  validator?: ConditionValidator;
};

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

export type OperatorValue = 'eq' | 'lt' | 'le' | 'gt' | 'ge' | 'in' | 'like';
export type Operator =
  | { name: 'eq'; label: 'equals'; value: 'eq' }
  | { name: 'lt'; label: 'less than'; value: 'lt' }
  | { name: 'le'; label: 'less than or equal to'; value: 'le' }
  | { name: 'gt'; label: 'greater than'; value: 'gt' }
  | { name: 'ge'; label: 'greater than or equal to'; value: 'ge' }
  | { name: 'in'; label: 'in'; value: 'in' }
  | { name: 'like'; label: 'like'; value: 'like' };

// condition and condition groups
export type Path = number[];

export type Combinator = 'and' | 'or';
export type Condition = {
  path?: Path;
  id?: string;
  fact: string;
  operator: Operator['value'];
  value: any;
};

export type ConditionGroup = {
  path?: Path;
  id?: string;
  combinator: Combinator;
  conditions: Array<Condition | ConditionGroup>;
};

export type ConditionOrGroup = Condition | ConditionGroup;

// validation
export type ValidationResult = {
  valid: boolean;
  reasons: Array<string>;
};

export type ValidationMap = Record<string, boolean | ValidationResult>;

export type ConditionValidator = (condition: Condition) => boolean | ValidationResult;

export type RuleValidator = (rule: Rule) => {
  condition: boolean | ValidationMap;
  actions: any;
};

// actions
export type Action<
  T extends string = string,
  P extends Record<string, any> = Record<string, any>,
> = {
  type: T;
  params?: P;
};

// rules
export type Rule = {
  condition: ConditionGroup;
  actions: Array<Action>;
};

// SR = ShopifyRule
export type SRCombinator = 'all' | 'any';
export type SRCondition = {
  fact: string;
  op: Operator['value'];
  val: any;
};
export type SRConditionGroup = Partial<{
  [K in SRCombinator]: Array<SRConditionOrGroup>;
}>;
export type SRConditionOrGroup = SRCondition | SRConditionGroup;
export type ShopifyRule = {
  condition: SRConditionGroup;
  actions: Array<Action>;
};

// react context
export type RuleCreatorContextType = {
  [prop: string]: any;
};
