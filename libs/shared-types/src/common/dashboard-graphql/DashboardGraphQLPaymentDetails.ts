import { DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLPaymentDetails = {
  __typename?: 'DashboardGraphQLPaymentDetails';
  bank?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  cardId?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  fee?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Int']>;
  international?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Boolean']>;
  invoiceId?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  method?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  refundStatus?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  tax?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Int']>;
  vpa?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  wallet?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
};