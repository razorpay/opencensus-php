import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLOrder } from './index';
export type DashboardGraphQLOnboardingPaymentOrderCreateSuccessResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLOnboardingPaymentOrderCreateSuccessResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  order: DashboardGraphQLOrder;
  success: DashboardGraphQLScalars['Boolean'];
};