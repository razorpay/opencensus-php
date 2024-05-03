export interface GatewayCoverage {
  method: string;
  enabled: boolean;
  MethodType?: {
    [key: string]: object | boolean;
  };
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
}
