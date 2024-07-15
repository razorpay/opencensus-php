export interface Operator {
  name: string;
  description: string;
  id: number;
  type: string;
  input_type: string;
  value: string;
}

export interface Parameter {
  name: string;
  value: string;
  values: {
    value: string;
    label?: string;
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
