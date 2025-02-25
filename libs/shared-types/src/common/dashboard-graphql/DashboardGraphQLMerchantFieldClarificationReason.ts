import { DashboardGraphQLScalars, DashboardGraphQLMerchantClarificationFromEnum, DashboardGraphQLMerchantClarificationTypeEnum } from './index';
export type DashboardGraphQLMerchantFieldClarificationReason = {
  __typename?: 'DashboardGraphQLMerchantFieldClarificationReason';
  comment: DashboardGraphQLScalars['String'];
  count: DashboardGraphQLScalars['PositiveInt'];
  createdAt: DashboardGraphQLScalars['DateTime'];
  isCurrentClarification: DashboardGraphQLScalars['Boolean'];
  sender: DashboardGraphQLMerchantClarificationFromEnum;
  type: DashboardGraphQLMerchantClarificationTypeEnum;
};