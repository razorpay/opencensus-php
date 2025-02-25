import { DashboardGraphQLMerchantApiKeyInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLMerchantApiKeyRegenerateOld = DashboardGraphQLMerchantApiKeyInterface & {
  __typename?: 'DashboardGraphQLMerchantApiKeyRegenerateOld';
  createdAt: DashboardGraphQLScalars['DateTime'];
  expiredAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  id: DashboardGraphQLScalars['String'];
  updatedAt: DashboardGraphQLScalars['DateTime'];
};