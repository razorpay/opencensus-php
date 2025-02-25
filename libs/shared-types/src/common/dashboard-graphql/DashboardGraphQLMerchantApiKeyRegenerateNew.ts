import { DashboardGraphQLMerchantApiKeyInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLMerchantApiKeyRegenerateNew = DashboardGraphQLMerchantApiKeyInterface & {
  __typename?: 'DashboardGraphQLMerchantApiKeyRegenerateNew';
  createdAt: DashboardGraphQLScalars['DateTime'];
  expiredAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  id: DashboardGraphQLScalars['String'];
  secret: DashboardGraphQLScalars['String'];
  updatedAt: DashboardGraphQLScalars['DateTime'];
};