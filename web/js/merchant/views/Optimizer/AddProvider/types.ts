export interface GatewayCoverage {
  method: string;
  enabled: boolean;
  card?: object;
  netbanking?: object;
  upi?: object;
  wallets?: object;
}

export interface Coverage {
  [key: string]: boolean;
}

export interface IntegrationStep {
  title: string;
  value: string;
  active: boolean;
  success: boolean;
  failed: boolean;
  blocked: boolean;
}
