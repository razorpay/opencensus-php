import { DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLMerchantOnboardingQuestionDetail } from './index';
export type DashboardGraphQLMerchantOnboardingQuestionDetailsSuccessResponse = {
  __typename?: 'DashboardGraphQLMerchantOnboardingQuestionDetailsSuccessResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  questionDetails: Array<DashboardGraphQLMerchantOnboardingQuestionDetail>;
  success: DashboardGraphQLScalars['Boolean'];
};