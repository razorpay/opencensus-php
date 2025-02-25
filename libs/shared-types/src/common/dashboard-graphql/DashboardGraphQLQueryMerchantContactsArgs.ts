import { DashboardGraphQLScalars, DashboardGraphQLInputMaybe, DashboardGraphQLPhoneInput } from './index';
export type DashboardGraphQLQueryMerchantContactsArgs = {
  active: DashboardGraphQLScalars['Boolean'];
  email?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['EmailAddress']>;
  emailText?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  fromDate?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['DateTime']>;
  fundAccountId?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  limit?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['PositiveInt']>;
  name?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  offset?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['NonNegativeInt']>;
  phone?: DashboardGraphQLInputMaybe<DashboardGraphQLPhoneInput>;
  reference?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  toDate?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['DateTime']>;
  type?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
};