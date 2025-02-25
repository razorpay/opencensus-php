import { DashboardGraphQLConfigData, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMerchantOnboardingConfig = {
  __typename?: 'DashboardGraphQLMerchantOnboardingConfig';
  configData: DashboardGraphQLConfigData;
  configType: DashboardGraphQLScalars['String'];
  success: DashboardGraphQLScalars['Boolean'];
};