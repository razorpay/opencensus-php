import { DashboardGraphQLPhoneInput, DashboardGraphQLInputMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMutationMerchantSendMobileOtpArgs = {
  contact: DashboardGraphQLPhoneInput;
  mockSend?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['Boolean']>;
};