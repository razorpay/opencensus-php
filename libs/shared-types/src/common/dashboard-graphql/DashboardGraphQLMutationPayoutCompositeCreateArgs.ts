import { DashboardGraphQLMoneyInput, DashboardGraphQLScalars, DashboardGraphQLMerchantContactFundAccountTypeEnum, DashboardGraphQLPayoutCompositeMerchantContactInput, DashboardGraphQLPayoutModeEnum, DashboardGraphQLInputMaybe, DashboardGraphQLMerchantContactFundAccountVpaInput } from './index';
export type DashboardGraphQLMutationPayoutCompositeCreateArgs = {
  amount: DashboardGraphQLMoneyInput;
  bankingAccountNumber: DashboardGraphQLScalars['String'];
  fundAccountType: DashboardGraphQLMerchantContactFundAccountTypeEnum;
  merchantContact: DashboardGraphQLPayoutCompositeMerchantContactInput;
  mode: DashboardGraphQLPayoutModeEnum;
  narration?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  notes?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['JSONObject']>;
  otp: DashboardGraphQLScalars['String'];
  purpose: DashboardGraphQLScalars['String'];
  queueOnLowBalance: DashboardGraphQLScalars['Boolean'];
  token: DashboardGraphQLScalars['String'];
  vpa: DashboardGraphQLMerchantContactFundAccountVpaInput;
};