import { DashboardGraphQLInputMaybe, DashboardGraphQLScalars, DashboardGraphQLPhoneInput } from './index';
export type DashboardGraphQLMutationLoginOtpResendArgs = {
  mockSend?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['Boolean']>;
  phone: DashboardGraphQLPhoneInput;
  token?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
};