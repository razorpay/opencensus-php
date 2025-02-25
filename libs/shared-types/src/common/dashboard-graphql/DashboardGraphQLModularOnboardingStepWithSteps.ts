import { DashboardGraphQLModularOnboardingStepInterface, DashboardGraphQLMaybe, DashboardGraphQLStepMeta, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLModularOnboardingStepWithSteps = DashboardGraphQLModularOnboardingStepInterface & {
  __typename?: 'DashboardGraphQLModularOnboardingStepWithSteps';
  meta?: DashboardGraphQLMaybe<DashboardGraphQLStepMeta>;
  name: DashboardGraphQLScalars['String'];
  progress: DashboardGraphQLScalars['Float'];
  status: DashboardGraphQLScalars['String'];
  steps: Array<DashboardGraphQLMaybe<DashboardGraphQLModularOnboardingStepInterface>>;
};