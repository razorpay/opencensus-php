export interface Operator {
  name: string;
  description: string;
  id: number;
  type: string;
  input_type: string;
  value: string;
}

export interface LogicalOperator {
  name: string;
  description: string;
  value: string;
  id: number;
}

export interface Parameter {
  name: string;
  value: string;
  values: {
    value: string;
    label?: string;
    disabled?: boolean;
    disabled_message?: string;
  }[];
  operators: {
    '=='?: {
      multiple?: boolean;
      type: string;
      number?: boolean;
    };
    in?: {
      multiple: boolean;
      type: string;
      number?: boolean;
    };
    '!='?: {
      multiple?: boolean;
      type: string;
      number?: boolean;
    };
    starting_with?: {
      type: string;
      number: boolean;
    };
    ending_with?: {
      type: string;
      number: boolean;
    };
    '>'?: {
      number?: boolean;
      multiple?: boolean;
      type: string;
    };
    '<'?: {
      number?: boolean;
      multiple?: boolean;
      type: string;
    };
    '>='?: {
      number?: boolean;
      multiple?: boolean;
      type: string;
    };
    '<='?: {
      number?: boolean;
      multiple?: boolean;
      type: string;
    };
    between?: {
      number: boolean;
      between: boolean;
      type: string;
    };
  };
  description: string;
  type: string;
  id: number;
}

export interface MappedProiders {
  id: string;
  name: string;
  value: string;
  disabled?: boolean;
  disabled_message?: string;
  image_url?: string;
}

export interface Option {
  id: string;
  name: string;
  description: string;
  disabled?: boolean;
  disabled_message?: string;
}

export interface Rule {
  id?: string;
  name: string;
  description?: string;
  default_expression?: null | undefined | any;
  expression: {
    type: string;
    value: string;
    operands: {
      type: string;
      value: string;
      operands: {
        type: string;
        value: string;
        operands: null | undefined;
      }[];
    }[];
  };
  score?: number | undefined;
  skip_on_failure: boolean;
  created_by?: string;
  created_at?: string;
  updated_at?: string;
  additional_attribute: {
    name: string;
    value: string;
  }[];
  indexable?: boolean;
  mode?: null | undefined;
  canary?: {
    use_canary: boolean;
    rule: null | undefined;
    ramp_percent: number;
  };
}

export interface Operands {
  type: string | null;
  value: string;
  operands:
    | {
        type: string;
        value: string;
        operands: null | undefined;
      }[]
    | null;
}

export interface Precondition {
  type: string | null;
  value: string;
  operands: Operands[] | Operands;
}

export interface RuleGroup {
  id: string;
  name: string;
  description: string;
  outcome_type: string;
  strategy: string;
  mandatory_attributes: any[];
  additional_attributes: {
    name: string;
    type: string;
    values: string[];
  }[];
  precondition: Precondition;
  rules: Rule[];
  created_by: string;
  created_at: string;
  updated_at: string;
  current?: boolean;
}

export interface Provider {
  Provider_name: string;
  Description: string;
  Gateway: string;
  Gateway_details: {
    [key: string]: string | number | boolean | string[] | { wallets: string[] };
    'Payment Methods': string[];
  };
  Currency: string[];
  Gateway_acquirer: string;
  Terminal_id: string;
  Status: string;
  created_at: number;
  updated_at: number;
}
