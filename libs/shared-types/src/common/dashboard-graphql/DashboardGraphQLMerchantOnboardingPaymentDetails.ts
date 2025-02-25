import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLOnboardingPaymentEligibilityEnum, DashboardGraphQLOnboardingManager, DashboardGraphQLOnboardingPaymentStatusEnum } from './index';
export type DashboardGraphQLMerchantOnboardingPaymentDetails = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLMerchantOnboardingPaymentDetails';
  code: DashboardGraphQLScalars['PositiveInt'];
  eligibility?: DashboardGraphQLMaybe<DashboardGraphQLOnboardingPaymentEligibilityEnum>;
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  onboardingManager?: DashboardGraphQLMaybe<DashboardGraphQLOnboardingManager>;
  orderId?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  paymentStatus?: DashboardGraphQLMaybe<DashboardGraphQLOnboardingPaymentStatusEnum>;
  success: DashboardGraphQLScalars['Boolean'];
};