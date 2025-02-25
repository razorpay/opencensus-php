import { DashboardGraphQLModularOnboardingStepInterface, DashboardGraphQLMaybe, DashboardGraphQLStepMeta, DashboardGraphQLModularComponent, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLModularOnboardingStepWithModularComponents = DashboardGraphQLModularOnboardingStepInterface & {
  __typename?: 'DashboardGraphQLModularOnboardingStepWithModularComponents';
  meta?: DashboardGraphQLMaybe<DashboardGraphQLStepMeta>;
  modularComponents: Array<DashboardGraphQLMaybe<DashboardGraphQLModularComponent>>;
  name: DashboardGraphQLScalars['String'];
  progress: DashboardGraphQLScalars['Float'];
  status: DashboardGraphQLScalars['String'];
};