import { DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLMerchantGstinUpdate = {
  __typename?: 'DashboardGraphQLMerchantGstinUpdate';
  gstin: DashboardGraphQLScalars['String'];
  gstinCertificateFileId: DashboardGraphQLScalars['String'];
  gstinCertificateId: DashboardGraphQLScalars['String'];
  isGstinAddOperation: DashboardGraphQLScalars['Boolean'];
  isSyncFlow: DashboardGraphQLScalars['Boolean'];
  isWorkFlowCreated?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Boolean']>;
  validationId: DashboardGraphQLScalars['ID'];
};