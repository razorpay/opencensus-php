import { DashboardGraphQLInputMaybe, DashboardGraphQLScalars, DashboardGraphQLPhoneInput } from './index';
export type DashboardGraphQLMutationMerchantContactUpdateArgs = {
  active?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['Boolean']>;
  email?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['EmailAddress']>;
  id: DashboardGraphQLScalars['ID'];
  name?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  notes?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['JSON']>;
  phone?: DashboardGraphQLInputMaybe<DashboardGraphQLPhoneInput>;
  reference?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  type?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
};