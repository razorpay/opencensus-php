export interface GatewayCoverage {
  [key: string]: {
    supported: boolean;
    types?: {
      [key: string]: boolean;
    };
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
