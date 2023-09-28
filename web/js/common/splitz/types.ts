import { ABVariable, ABVariant as DefaultABVariant } from '@razorpay/universe-cli/ab';
import type { RouteComponentProps } from 'common/deprecated/RouteComponentProps';
import type { PathPattern } from 'react-router-dom';

export enum MandatoryEnvEnum {
  'beta' = 'beta',
  'production' = 'production',
}

export enum EnvironmentEnum {
  'stage' = 'stage',
  'devstack' = 'devstack',
  'automation' = 'automation',
  'automation1' = 'automation1',
}

export enum ActiveDashboardTypeEnum {
  'product' = 'product',
  'partner' = 'partner',
  'linkedAccount' = 'linkedAccount',
  'pokedex' = 'pokedex',
}

export enum RootDashboardTypeEnum {
  'merchant' = 'merchant',
  'linkedAccount' = 'linkedAccount',
  'pokedex' = 'pokedex',
}

export type RootDashboardType = keyof typeof RootDashboardTypeEnum;

export type ActiveDashboardType = keyof typeof ActiveDashboardTypeEnum;

export type EnvironmentType = keyof typeof EnvironmentEnum;

export type MandatoryEnvironmentType = keyof typeof MandatoryEnvEnum;

export type VariantConfigArgs = {
  uniqueHashKey: string;
  experimentId: Partial<Record<EnvironmentType, string>> & Record<MandatoryEnvironmentType, string>;
  defaultVariant: DefaultVariantType;
};

export type DefaultVariantType = {
  name: string;
  variables: ABVariable[];
};

export type InitABServiceConfig = {
  apiBaseUrl: string;
  merchantId: string;
};

export type ParsedABVariable = Record<string, unknown>;

export type ExperimentType = {
  experimentId: string;
  variables: ParsedABVariable;
};

export type RouteObject = PathPattern;
export type RouteMatchConfig = RouteObject | string | RegExp;

export type RouteBasedMapConfig = {
  matchByDashboard?: ActiveDashboardType[];
  routesToMatch: RouteMatchConfig[];
  abExperiments: VariantConfigArgs[];
};

export interface SplitzInitConfig {
  onInit: Record<'default' | RootDashboardType, VariantConfigArgs[]>;
  routeBased: RouteBasedMapConfig[];
}

// The type and api res is not in sync. So creating a synced one.
export type ABVariant = Pick<DefaultABVariant, 'id' | 'name' | 'variables'> & {
  experiment_id: string;
};

export type ExperimentInfoType = Record<string, ExperimentType>;

export interface SplitzServiceActionType {
  setInitialized: (payload: boolean) => void;
  setABExperiments: (payload: ExperimentInfoType) => void;
}

export type SpiltzContextState = {
  /**
   * Pass your experiment's `uniqueHashKey` to check if its evaluated or not. Please make sure
   * to add your experiment in dashboard's config.
   */
  isExperimentEvaluated: (experimentHashKey: string) => boolean;
  /**
   * A promise to evaluate an experiment. Once promise is resolved,
   * the evaluated experiment can be consumed via `abExperiments`.
   */
  evaluateExperiment: (experimentToEvaluate: VariantConfigArgs) => Promise<void>;
  /**
   * A promise to evaluate experiments in bulk. Once promise is resolved,
   * the evaluated experiments can be consumed via `abExperiments`.
   */
  bulkEvaluateExperiments: (experimentsToEvaluate: VariantConfigArgs[]) => Promise<void>;
  /**
   * A `boolean` that will be set to true once splitz service is initialized and experiments via `onInit`
   * configured w.r.t the environment is evaluated. The evaluated experiments can be consumed via `abExperiments`
   */
  isInitialized: boolean;
  /**
   * State that holds all of the evaluated experiments.
   */
  abExperiments: ExperimentInfoType;
  /**
   * Currently viewing dashboard type.
   */
  activeDashboard: ActiveDashboardType;
};

export { ABVariable };

export interface SpiltzServiceProviderProps extends RouteComponentProps {
  children: JSX.Element | JSX.Element[];
  dashboardType: RootDashboardType;
  customLoader?: () => JSX.Element;
}

export type UseSplitzReducerReturnType = {
  setInitialized: (payload: boolean) => void;
  setABExperiments: (payload: ExperimentInfoType) => void;
  isInitialized: boolean;
  abExperiments: ExperimentInfoType;
};
