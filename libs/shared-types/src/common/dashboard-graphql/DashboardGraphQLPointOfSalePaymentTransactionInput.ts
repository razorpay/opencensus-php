import { DashboardGraphQLInputMaybe, DashboardGraphQLScalars, DashboardGraphQLPointOfSalePaymentTransactionStatus } from './index';
export type DashboardGraphQLPointOfSalePaymentTransactionInput = {
  authorizationCode?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  description?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  paymentStatus: DashboardGraphQLPointOfSalePaymentTransactionStatus;
  resultCode: DashboardGraphQLScalars['String'];
  retrievalReferenceNumber?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  statusCode?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  transactionId?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
};