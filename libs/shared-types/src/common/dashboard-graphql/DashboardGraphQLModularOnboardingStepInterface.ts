import { DashboardGraphQLMaybe, DashboardGraphQLStepMeta, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLModularOnboardingStepInterface = {
  meta?: DashboardGraphQLMaybe<DashboardGraphQLStepMeta>;
  name: DashboardGraphQLScalars['String'];
  progress: DashboardGraphQLScalars['Float'];
  status: DashboardGraphQLScalars['String'];
};