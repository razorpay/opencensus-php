import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLOnboardingPaymentOrderCreateFailureResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLOnboardingPaymentOrderCreateFailureResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};