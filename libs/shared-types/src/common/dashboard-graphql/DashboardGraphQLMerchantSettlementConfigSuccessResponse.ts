import { DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLMerchantSettlementConfigSuccessResponse = {
  __typename?: 'DashboardGraphQLMerchantSettlementConfigSuccessResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  fundsOnHold: DashboardGraphQLScalars['Boolean'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  settlementBlocked: DashboardGraphQLScalars['Boolean'];
  settlementsOnHold: DashboardGraphQLScalars['Boolean'];
  success: DashboardGraphQLScalars['Boolean'];
};