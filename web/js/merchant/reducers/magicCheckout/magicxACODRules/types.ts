export type RuleType = 'shipping' | 'payment';

export type Rule<T extends RuleType = RuleType> = {
  merchant_id: string;
  id: string;
  name: string;
  description: string;
  type: T;
  rule: string;
};

export type RuleFactType = 'string' | 'number' | 'boolean';
export type RuleFact = {
  name: string;
  type: RuleType;
  label: string;
};

export type MagicXACODRulesState = {
  isLoading: {
    rules: boolean;
    ruleFacts: boolean;
  };
  rules: Array<Rule>;
  ruleFacts: [];
  ruleLimits: {
    shipping: number;
    payment: number;
  };
};
