import { DashboardGraphQLMerchantEmailField, DashboardGraphQLMerchantStringField, DashboardGraphQLMerchantPhoneField } from './index';
export type DashboardGraphQLMerchantContactPerson = {
  __typename?: 'DashboardGraphQLMerchantContactPerson';
  email: DashboardGraphQLMerchantEmailField;
  name: DashboardGraphQLMerchantStringField;
  phone: DashboardGraphQLMerchantPhoneField;
};