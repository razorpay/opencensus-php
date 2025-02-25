import { DashboardGraphQLInputMaybe, DashboardGraphQLScalars, DashboardGraphQLPhoneInput } from './index';
export type DashboardGraphQLMutationLoginOtpArgs = {
  clientId?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  mockSend?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['Boolean']>;
  onboardingSignature?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  phone: DashboardGraphQLPhoneInput;
};