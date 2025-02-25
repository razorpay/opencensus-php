import { DashboardGraphQLMerchantActivationFlowEnum, DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLMerchantBusinessSubCategory = {
  __typename?: 'DashboardGraphQLMerchantBusinessSubCategory';
  activationFlow: DashboardGraphQLMerchantActivationFlowEnum;
  name: DashboardGraphQLScalars['String'];
  nonRegisteredActivationFlow: DashboardGraphQLMerchantActivationFlowEnum;
  tags: Array<DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>>;
  value: DashboardGraphQLScalars['String'];
};