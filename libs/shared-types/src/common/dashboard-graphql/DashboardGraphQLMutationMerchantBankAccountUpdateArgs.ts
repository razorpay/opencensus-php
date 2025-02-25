import { DashboardGraphQLScalars, DashboardGraphQLInputMaybe } from './index';
export type DashboardGraphQLMutationMerchantBankAccountUpdateArgs = {
  accountNumber: DashboardGraphQLScalars['String'];
  addressProofDocument?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['Upload']>;
  beneficiaryName: DashboardGraphQLScalars['String'];
  ifscCode: DashboardGraphQLScalars['String'];
  isSyncOnly?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['Boolean']>;
};