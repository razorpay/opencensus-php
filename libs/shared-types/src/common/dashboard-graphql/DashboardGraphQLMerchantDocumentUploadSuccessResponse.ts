import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMerchantDocumentUpload, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLMerchantDocumentUploadSuccessResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLMerchantDocumentUploadSuccessResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  documentUpload: DashboardGraphQLMerchantDocumentUpload;
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};