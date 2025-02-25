import { DashboardGraphQLMaybe, DashboardGraphQLModularOnboardingConfig, DashboardGraphQLOnboardingState } from './index';
export type DashboardGraphQLModularOnboardingData = {
  __typename?: 'DashboardGraphQLModularOnboardingData';
  config?: DashboardGraphQLMaybe<DashboardGraphQLModularOnboardingConfig>;
  state?: DashboardGraphQLMaybe<DashboardGraphQLOnboardingState>;
};