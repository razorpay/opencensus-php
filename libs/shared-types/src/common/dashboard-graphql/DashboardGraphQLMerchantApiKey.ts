import { DashboardGraphQLMerchantApiKeyInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLMerchantApiKey = DashboardGraphQLMerchantApiKeyInterface & {
  __typename?: 'DashboardGraphQLMerchantApiKey';
  createdAt: DashboardGraphQLScalars['DateTime'];
  expiredAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  id: DashboardGraphQLScalars['String'];
  updatedAt: DashboardGraphQLScalars['DateTime'];
};