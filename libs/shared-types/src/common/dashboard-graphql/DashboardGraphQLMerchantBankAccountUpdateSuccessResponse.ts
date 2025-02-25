import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLMaybe, DashboardGraphQLMerchantBankAccountDetails, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMerchantBankAccountUpdateSuccessResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLMerchantBankAccountUpdateSuccessResponse';
  bankAccountDetails?: DashboardGraphQLMaybe<DashboardGraphQLMerchantBankAccountDetails>;
  code: DashboardGraphQLScalars['PositiveInt'];
  isSyncFlow: DashboardGraphQLScalars['Boolean'];
  isTimeOut: DashboardGraphQLScalars['Boolean'];
  isWorkFlowCreated: DashboardGraphQLScalars['Boolean'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};