import { DashboardGraphQLMaybe, DashboardGraphQLUserRoleBankingEnum, DashboardGraphQLScalars, DashboardGraphQLMerchantBankingRole, DashboardGraphQLMerchant, DashboardGraphQLUserRolePaymentsEnum } from './index';
export type DashboardGraphQLUserRole = {
  __typename?: 'DashboardGraphQLUserRole';
  banking?: DashboardGraphQLMaybe<DashboardGraphQLUserRoleBankingEnum>;
  bankingPermissions: Array<DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>>;
  bankingRole?: DashboardGraphQLMaybe<DashboardGraphQLMerchantBankingRole>;
  merchant: DashboardGraphQLMerchant;
  payments?: DashboardGraphQLMaybe<DashboardGraphQLUserRolePaymentsEnum>;
};