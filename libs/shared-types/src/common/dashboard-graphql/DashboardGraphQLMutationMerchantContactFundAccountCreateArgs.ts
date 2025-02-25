import { DashboardGraphQLInputMaybe, DashboardGraphQLMerchantContactFundAccountBankAccountInput, DashboardGraphQLScalars, DashboardGraphQLMerchantContactFundAccountTypeEnum, DashboardGraphQLMerchantContactFundAccountVpaInput } from './index';
export type DashboardGraphQLMutationMerchantContactFundAccountCreateArgs = {
  bankAccount?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantContactFundAccountBankAccountInput>;
  contactId: DashboardGraphQLScalars['ID'];
  type: DashboardGraphQLMerchantContactFundAccountTypeEnum;
  vpa?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantContactFundAccountVpaInput>;
};