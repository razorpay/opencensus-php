import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLMerchantPaymentHandle } from './index';
export type DashboardGraphQLMerchantPaymentHandleCreateSuccessResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLMerchantPaymentHandleCreateSuccessResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  paymentHandle: DashboardGraphQLMerchantPaymentHandle;
  success: DashboardGraphQLScalars['Boolean'];
};