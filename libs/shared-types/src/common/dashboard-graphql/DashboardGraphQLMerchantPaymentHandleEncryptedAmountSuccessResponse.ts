import { DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLMerchantPaymentHandleEncryptedAmountSuccessResponse = {
  __typename?: 'DashboardGraphQLMerchantPaymentHandleEncryptedAmountSuccessResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  encryptedAmount: DashboardGraphQLScalars['String'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};