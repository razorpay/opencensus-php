import { DashboardGraphQLMaybe, DashboardGraphQLPageAcquirerData, DashboardGraphQLTransactionAmount, DashboardGraphQLScalars, DashboardGraphQLTransactionError, DashboardGraphQLPaymentDetails, DashboardGraphQLTransactionStatus, DashboardGraphQLUserContactDetails } from './index';
export type DashboardGraphQLPaymentPageTransaction = {
  __typename?: 'DashboardGraphQLPaymentPageTransaction';
  acquirerData?: DashboardGraphQLMaybe<DashboardGraphQLPageAcquirerData>;
  amount?: DashboardGraphQLMaybe<DashboardGraphQLTransactionAmount>;
  createdAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  description?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  entity?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  error?: DashboardGraphQLMaybe<DashboardGraphQLTransactionError>;
  id: DashboardGraphQLScalars['String'];
  notes?: DashboardGraphQLMaybe<DashboardGraphQLScalars['JSONObject']>;
  paymentDetails?: DashboardGraphQLMaybe<DashboardGraphQLPaymentDetails>;
  status?: DashboardGraphQLMaybe<DashboardGraphQLTransactionStatus>;
  userDetails?: DashboardGraphQLMaybe<DashboardGraphQLUserContactDetails>;
};