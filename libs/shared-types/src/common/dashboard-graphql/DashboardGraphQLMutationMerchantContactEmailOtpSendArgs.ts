import { DashboardGraphQLScalars, DashboardGraphQLInputMaybe } from './index';
export type DashboardGraphQLMutationMerchantContactEmailOtpSendArgs = {
  email: DashboardGraphQLScalars['EmailAddress'];
  otpVerificationToken?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
};