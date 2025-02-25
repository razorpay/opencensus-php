import { DashboardGraphQLMaybe, DashboardGraphQLMerchantActivationFlowEnum } from './index';
export type DashboardGraphQLMerchantActivationFlow = {
  __typename?: 'DashboardGraphQLMerchantActivationFlow';
  domestic?: DashboardGraphQLMaybe<DashboardGraphQLMerchantActivationFlowEnum>;
  international?: DashboardGraphQLMaybe<DashboardGraphQLMerchantActivationFlowEnum>;
};