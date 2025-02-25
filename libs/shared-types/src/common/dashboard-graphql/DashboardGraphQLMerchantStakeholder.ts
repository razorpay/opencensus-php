import { DashboardGraphQLMaybe, DashboardGraphQLMerchantVerificationStatusEnum, DashboardGraphQLScalars, DashboardGraphQLMerchantStringField } from './index';
export type DashboardGraphQLMerchantStakeholder = {
  __typename?: 'DashboardGraphQLMerchantStakeholder';
  aadharEsignStatus?: DashboardGraphQLMaybe<DashboardGraphQLMerchantVerificationStatusEnum>;
  aadharPin?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  isAadharLinked?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Boolean']>;
  name: DashboardGraphQLMerchantStringField;
  pan: DashboardGraphQLMerchantStringField;
  panVerificationStatus?: DashboardGraphQLMaybe<DashboardGraphQLMerchantVerificationStatusEnum>;
};