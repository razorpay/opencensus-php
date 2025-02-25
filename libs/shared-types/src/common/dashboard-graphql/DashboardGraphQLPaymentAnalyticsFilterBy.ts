import { DashboardGraphQLInputMaybe, DashboardGraphQLPaymentAnalyticsFilterByDeviceEnum, DashboardGraphQLPaymentAnalyticsFilterByOsEnum, DashboardGraphQLPaymentAnalyticsFilterByPaymentEnum, DashboardGraphQLPaymentAnalyticsFilterBySdkEnum } from './index';
export type DashboardGraphQLPaymentAnalyticsFilterBy = {
  device?: DashboardGraphQLInputMaybe<Array<DashboardGraphQLPaymentAnalyticsFilterByDeviceEnum>>;
  os?: DashboardGraphQLInputMaybe<Array<DashboardGraphQLPaymentAnalyticsFilterByOsEnum>>;
  payment?: DashboardGraphQLInputMaybe<Array<DashboardGraphQLPaymentAnalyticsFilterByPaymentEnum>>;
  sdk?: DashboardGraphQLInputMaybe<Array<DashboardGraphQLPaymentAnalyticsFilterBySdkEnum>>;
};