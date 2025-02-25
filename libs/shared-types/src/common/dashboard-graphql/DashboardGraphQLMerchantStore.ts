import { DashboardGraphQLScalars, DashboardGraphQLCurrencyCodeEnum, DashboardGraphQLMaybe, DashboardGraphQLMerchantStoreStatus, DashboardGraphQLPhone } from './index';
export type DashboardGraphQLMerchantStore = {
  __typename?: 'DashboardGraphQLMerchantStore';
  createdAt: DashboardGraphQLScalars['DateTime'];
  currency: DashboardGraphQLCurrencyCodeEnum;
  deletedAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  description?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  expireBy?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  expiredAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
  id: DashboardGraphQLScalars['ID'];
  slug?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  status: DashboardGraphQLMerchantStoreStatus;
  supportContact?: DashboardGraphQLMaybe<DashboardGraphQLPhone>;
  supportEmail?: DashboardGraphQLMaybe<DashboardGraphQLScalars['EmailAddress']>;
  title?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  updatedAt: DashboardGraphQLScalars['DateTime'];
  url: DashboardGraphQLScalars['URL'];
};