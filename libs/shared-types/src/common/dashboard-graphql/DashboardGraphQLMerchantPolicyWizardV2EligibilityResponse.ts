import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLMerchantPolicyEligibilityEnum, DashboardGraphQLMerchantVerificationStatusEnum } from './index';
export type DashboardGraphQLMerchantPolicyWizardV2EligibilityResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLMerchantPolicyWizardV2EligibilityResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  isPolicyWizardV2Enabled: DashboardGraphQLScalars['Boolean'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  policyEligibility?: DashboardGraphQLMaybe<DashboardGraphQLMerchantPolicyEligibilityEnum>;
  success: DashboardGraphQLScalars['Boolean'];
  websitePolicyVerificationStatus?: DashboardGraphQLMaybe<DashboardGraphQLMerchantVerificationStatusEnum>;
};