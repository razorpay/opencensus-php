import { DashboardGraphQLInputMaybe, DashboardGraphQLScalars, DashboardGraphQLPhoneInput } from './index';
export type DashboardGraphQLCustomerInput = {
  email?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['EmailAddress']>;
  id?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['ID']>;
  name?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  phone?: DashboardGraphQLInputMaybe<DashboardGraphQLPhoneInput>;
};