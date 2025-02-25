import { DashboardGraphQLScalars, DashboardGraphQLTransactionSourceBankTransferModeEnum, DashboardGraphQLTransactionSourceBankTransferPayee, DashboardGraphQLTransactionSourceBankTransferPayer } from './index';
export type DashboardGraphQLTransactionSourceBankTransfer = {
  __typename?: 'DashboardGraphQLTransactionSourceBankTransfer';
  id: DashboardGraphQLScalars['ID'];
  mode: DashboardGraphQLTransactionSourceBankTransferModeEnum;
  payee: DashboardGraphQLTransactionSourceBankTransferPayee;
  payer: DashboardGraphQLTransactionSourceBankTransferPayer;
  reference: DashboardGraphQLScalars['String'];
};