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
