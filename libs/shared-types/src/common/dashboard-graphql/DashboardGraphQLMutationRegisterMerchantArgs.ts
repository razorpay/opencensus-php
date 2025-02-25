import { DashboardGraphQLPhoneInput, DashboardGraphQLInputMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMutationRegisterMerchantArgs = {
  contact: DashboardGraphQLPhoneInput;
  mockSend?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['Boolean']>;
  token?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
};