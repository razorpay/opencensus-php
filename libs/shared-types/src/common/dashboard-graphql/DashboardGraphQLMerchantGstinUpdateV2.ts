import { DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLMerchantGstinUpdateVersionEnum } from './index';
export type DashboardGraphQLMerchantGstinUpdateV2 = {
  __typename?: 'DashboardGraphQLMerchantGstinUpdateV2';
  gstin: DashboardGraphQLScalars['String'];
  isSyncFlow: DashboardGraphQLScalars['Boolean'];
  isWorkFlowCreated?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Boolean']>;
  version: DashboardGraphQLMerchantGstinUpdateVersionEnum;
};