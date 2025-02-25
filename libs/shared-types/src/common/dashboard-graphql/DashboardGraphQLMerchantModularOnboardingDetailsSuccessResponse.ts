import { DashboardGraphQLScalars, DashboardGraphQLOnboardingState, DashboardGraphQLWorkflowData } from './index';
export type DashboardGraphQLMerchantModularOnboardingDetailsSuccessResponse = {
  __typename?: 'merchantModularOnboardingDetailsSuccessResponse';
  countryCode: DashboardGraphQLScalars['String'];
  merchantType: DashboardGraphQLScalars['String'];
  onboardingState: DashboardGraphQLOnboardingState;
  onboardingType: DashboardGraphQLScalars['String'];
  workflowData: DashboardGraphQLWorkflowData;
};