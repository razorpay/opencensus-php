import { DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLMerchantPaymentHandle } from './index';
export type DashboardGraphQLMerchantPaymentHandleSuccessResponse = {
  __typename?: 'DashboardGraphQLMerchantPaymentHandleSuccessResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  paymentHandle: DashboardGraphQLMerchantPaymentHandle;
  success: DashboardGraphQLScalars['Boolean'];
};