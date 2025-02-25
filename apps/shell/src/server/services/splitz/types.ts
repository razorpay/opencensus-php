import { ABVariable, ABVariant as DefaultABVariant } from '@razorpay/universe-cli/ab';

export enum MandatoryEnvEnum {
  'beta' = 'beta',
  'production' = 'production',
  'canary' = 'canary',
}

export type InitABServiceConfig = {
  apiBaseUrl: string;
  isInternalEndpointCall?: boolean;
  internalApiAuthToken?: string;
};

export enum EnvironmentEnum {
  'stage' = 'stage',
  'devstack' = 'devstack',
  'automation' = 'automation',
  'automation1' = 'automation1',
}

export type ParsedABVariable = Record<string, unknown>;

export type ExperimentType = {
  experimentId: string;
  variables: ParsedABVariable;
};

export type EvalExperimentResults = Record<
  string,
  {
    enabled: boolean;
    info: ExperimentType;
  }
>;

export type DefaultVariantType = {
  name: string;
  variables: ABVariable[];
};

export type EnvironmentType = keyof typeof EnvironmentEnum;

export type MandatoryEnvironmentType = keyof typeof MandatoryEnvEnum;

export type VariantConfigArgs = {
  uniqueHashKey: string;
  experimentId: Partial<Record<EnvironmentType, string>> & Record<MandatoryEnvironmentType, string>;
  defaultVariant: DefaultVariantType;
  evaluater: (variables: ParsedABVariable) => boolean;
};

// The type and api res is not in sync. So creating a synced one.
export type ABVariant = Pick<DefaultABVariant, 'id' | 'name' | 'variables'> & {
  experiment_id: string;
};
