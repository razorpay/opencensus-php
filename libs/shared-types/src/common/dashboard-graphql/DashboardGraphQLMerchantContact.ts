import { DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLMerchantContactFundAccountsResponse, DashboardGraphQLPaymentTerm, DashboardGraphQLPhone, DashboardGraphQLTdsCategory } from './index';
export type DashboardGraphQLMerchantContact = {
  __typename?: 'DashboardGraphQLMerchantContact';
  active: DashboardGraphQLScalars['Boolean'];
  createdAt: DashboardGraphQLScalars['DateTime'];
  email?: DashboardGraphQLMaybe<DashboardGraphQLScalars['EmailAddress']>;
  fundAccounts?: DashboardGraphQLMaybe<DashboardGraphQLMerchantContactFundAccountsResponse>;
  id: DashboardGraphQLScalars['ID'];
  name: DashboardGraphQLScalars['String'];
  notes?: DashboardGraphQLMaybe<DashboardGraphQLScalars['JSONObject']>;
  paymentTerm?: DashboardGraphQLMaybe<DashboardGraphQLPaymentTerm>;
  phone?: DashboardGraphQLMaybe<DashboardGraphQLPhone>;
  referenceId?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  tdsCategory?: DashboardGraphQLMaybe<DashboardGraphQLTdsCategory>;
  type?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
};