import { DashboardGraphQLInputMaybe, DashboardGraphQLPhoneInput, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMerchantStoreUpdateInput = {
  contact?: DashboardGraphQLInputMaybe<DashboardGraphQLPhoneInput>;
  email?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['EmailAddress']>;
  expireBy?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['DateTime']>;
  title?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
};