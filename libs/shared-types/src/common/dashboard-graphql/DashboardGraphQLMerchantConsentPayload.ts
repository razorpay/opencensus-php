import { DashboardGraphQLScalars, DashboardGraphQLMerchantConsentsTypeEnum, DashboardGraphQLInputMaybe } from './index';
export type DashboardGraphQLMerchantConsentPayload = {
  isProvided: DashboardGraphQLScalars['Boolean'];
  type: DashboardGraphQLMerchantConsentsTypeEnum;
  url?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['URL']>;
};