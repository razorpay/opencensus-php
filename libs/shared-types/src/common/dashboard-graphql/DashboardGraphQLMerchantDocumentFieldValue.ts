import { DashboardGraphQLMerchantDocumentFieldValueInterface, DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMerchantDocumentFieldValue = DashboardGraphQLMerchantDocumentFieldValueInterface & {
  __typename?: 'DashboardGraphQLMerchantDocumentFieldValue';
  createdAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  fileName?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  id: DashboardGraphQLScalars['ID'];
  /** @deprecated Unused */
  url?: DashboardGraphQLMaybe<DashboardGraphQLScalars['URL']>;
};