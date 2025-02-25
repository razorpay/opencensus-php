import { DashboardGraphQLInputMaybe, DashboardGraphQLScalars, DashboardGraphQLPhoneInput } from './index';
export type DashboardGraphQLMutationMerchantContactCreateArgs = {
  email?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['EmailAddress']>;
  name: DashboardGraphQLScalars['String'];
  notes?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['JSONObject']>;
  phone?: DashboardGraphQLInputMaybe<DashboardGraphQLPhoneInput>;
  reference?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  type?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
};