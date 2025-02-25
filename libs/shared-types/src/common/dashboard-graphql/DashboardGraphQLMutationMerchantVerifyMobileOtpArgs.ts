import { DashboardGraphQLPhoneInput, DashboardGraphQLInputMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMutationMerchantVerifyMobileOtpArgs = {
  contact: DashboardGraphQLPhoneInput;
  mockSend?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['Boolean']>;
  otp: DashboardGraphQLScalars['String'];
};