import { DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLDocumentUploadFieldValue = {
  __typename?: 'DashboardGraphQLDocumentUploadFieldValue';
  file_id?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  name?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  size?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Int']>;
};