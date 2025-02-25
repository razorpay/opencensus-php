import { DashboardGraphQLInputMaybe, DashboardGraphQLMerchantBankInput, DashboardGraphQLMerchantBusinessInput, DashboardGraphQLMerchantContactPersonInput, DashboardGraphQLMerchantDocumentInput, DashboardGraphQLMerchantStakeholderInput } from './index';
export type DashboardGraphQLFieldDetailsInput = {
  bank?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantBankInput>;
  business?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantBusinessInput>;
  contactPerson?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantContactPersonInput>;
  document?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantDocumentInput>;
  stakeholder?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantStakeholderInput>;
};