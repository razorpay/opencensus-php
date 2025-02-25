import { DashboardGraphQLScalars, DashboardGraphQLMerchantKycPartnerAccessTypeEnum } from './index';
export type DashboardGraphQLMerchantKycPartnerAccessResponse = {
  __typename?: 'MerchantKYCPartnerAccessResponse';
  partnerName: DashboardGraphQLScalars['String'];
  status: DashboardGraphQLMerchantKycPartnerAccessTypeEnum;
};