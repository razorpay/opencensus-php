import { DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLMerchantDocumentUploadPurposeEnum } from './index';
export type DashboardGraphQLMerchantDocumentUpload = {
  __typename?: 'DashboardGraphQLMerchantDocumentUpload';
  createdAt: DashboardGraphQLScalars['DateTime'];
  displayName: DashboardGraphQLScalars['String'];
  id: DashboardGraphQLScalars['ID'];
  mimeType?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  purpose: DashboardGraphQLMerchantDocumentUploadPurposeEnum;
  size: DashboardGraphQLScalars['PositiveInt'];
};