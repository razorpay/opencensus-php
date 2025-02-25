import { DashboardGraphQLMerchantDocumentFieldValueInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLMerchantDocumentByIdResponse = DashboardGraphQLMerchantDocumentFieldValueInterface & {
  __typename?: 'DashboardGraphQLMerchantDocumentByIdResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  createdAt: DashboardGraphQLScalars['DateTime'];
  fileName?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  id: DashboardGraphQLScalars['ID'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  signedUrl?: DashboardGraphQLMaybe<DashboardGraphQLScalars['URL']>;
  success: DashboardGraphQLScalars['Boolean'];
};