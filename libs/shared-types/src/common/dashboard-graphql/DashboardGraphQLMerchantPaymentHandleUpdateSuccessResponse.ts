import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLMerchantPaymentHandle } from './index';
export type DashboardGraphQLMerchantPaymentHandleUpdateSuccessResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLMerchantPaymentHandleUpdateSuccessResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  paymentHandle: DashboardGraphQLMerchantPaymentHandle;
  success: DashboardGraphQLScalars['Boolean'];
};