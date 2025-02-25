import { DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLMerchantApiKeyInterface = {
  createdAt: DashboardGraphQLScalars['DateTime'];
  expiredAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  id: DashboardGraphQLScalars['String'];
  updatedAt: DashboardGraphQLScalars['DateTime'];
};