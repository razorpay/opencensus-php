import { DashboardGraphQLScalars, DashboardGraphQLMerchantPaymentHandle, DashboardGraphQLPaymentsWidgetTypeEnum, DashboardGraphQLWidgetVariantEnum } from './index';
export type DashboardGraphQLPaymentHandleWidget = {
  __typename?: 'DashboardGraphQLPaymentHandleWidget';
  description: DashboardGraphQLScalars['String'];
  paymentHandle: DashboardGraphQLMerchantPaymentHandle;
  title: DashboardGraphQLScalars['String'];
  type: DashboardGraphQLPaymentsWidgetTypeEnum;
  variant: DashboardGraphQLWidgetVariantEnum;
};