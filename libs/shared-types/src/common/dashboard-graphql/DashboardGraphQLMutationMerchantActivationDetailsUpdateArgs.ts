import { DashboardGraphQLInputMaybe, DashboardGraphQLScalars, DashboardGraphQLMerchantActivationMilestoneEnum, DashboardGraphQLMerchantBankInput, DashboardGraphQLMerchantBusinessInput, DashboardGraphQLMerchantConsentInput, DashboardGraphQLMerchantContactPersonInput, DashboardGraphQLMerchantDocumentInput, DashboardGraphQLMerchantStakeholderInput } from './index';
export type DashboardGraphQLMutationMerchantActivationDetailsUpdateArgs = {
  activationDataSubmitted?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['Boolean']>;
  activationFormMilestone?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantActivationMilestoneEnum>;
  bank?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantBankInput>;
  business?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantBusinessInput>;
  consent?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantConsentInput>;
  contactPerson?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantContactPersonInput>;
  document?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantDocumentInput>;
  fingerprintRequestId?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  partnerId?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  posActivationDataSubmitted?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['Boolean']>;
  showSubcategoryRecommendations?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['Boolean']>;
  source?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  stakeholder?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantStakeholderInput>;
};