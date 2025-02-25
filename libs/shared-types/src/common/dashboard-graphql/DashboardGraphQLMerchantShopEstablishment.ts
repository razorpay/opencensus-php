import { DashboardGraphQLMaybe, DashboardGraphQLScalars, DashboardGraphQLMerchantStringField } from './index';
export type DashboardGraphQLMerchantShopEstablishment = {
  __typename?: 'DashboardGraphQLMerchantShopEstablishment';
  isVerifiableZone?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Boolean']>;
  number: DashboardGraphQLMerchantStringField;
};