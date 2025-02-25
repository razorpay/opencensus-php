import { DashboardGraphQLScalars, DashboardGraphQLInputMaybe } from './index';
export type DashboardGraphQLMutationSendEmailVerificationOtpArgs = {
  email: DashboardGraphQLScalars['String'];
  token?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
};