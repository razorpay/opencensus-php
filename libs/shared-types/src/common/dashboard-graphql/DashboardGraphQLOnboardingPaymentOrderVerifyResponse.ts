import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLOnboardingPaymentOrderVerifyResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLOnboardingPaymentOrderVerifyResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};