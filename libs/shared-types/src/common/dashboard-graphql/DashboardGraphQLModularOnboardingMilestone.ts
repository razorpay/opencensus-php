import { DashboardGraphQLMaybe, DashboardGraphQLScalars, DashboardGraphQLMilestoneMeta, DashboardGraphQLModularOnboardingStep } from './index';
export type DashboardGraphQLModularOnboardingMilestone = {
  __typename?: 'DashboardGraphQLModularOnboardingMilestone';
  canSubmit?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Boolean']>;
  meta?: DashboardGraphQLMaybe<DashboardGraphQLMilestoneMeta>;
  name: DashboardGraphQLScalars['String'];
  progress: DashboardGraphQLScalars['Float'];
  status: DashboardGraphQLScalars['String'];
  steps?: DashboardGraphQLMaybe<Array<DashboardGraphQLMaybe<DashboardGraphQLModularOnboardingStep>>>;
};