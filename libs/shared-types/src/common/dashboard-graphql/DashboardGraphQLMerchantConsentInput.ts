import { DashboardGraphQLInputMaybe, DashboardGraphQLScalars, DashboardGraphQLMerchantConsentData } from './index';
export type DashboardGraphQLMerchantConsentInput = {
  consent?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['Boolean']>;
  consentData?: DashboardGraphQLInputMaybe<Array<DashboardGraphQLInputMaybe<DashboardGraphQLMerchantConsentData>>>;
};