import { DashboardGraphQLInputMaybe, DashboardGraphQLMoneyInput, DashboardGraphQLScalars, DashboardGraphQLQrCodeTypeEnum, DashboardGraphQLQrCodeUsageEnum } from './index';
export type DashboardGraphQLMutationQrCodeCreateArgs = {
  amount?: DashboardGraphQLInputMaybe<DashboardGraphQLMoneyInput>;
  description?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  isFixedAmount?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['Boolean']>;
  name?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  type: DashboardGraphQLQrCodeTypeEnum;
  usage: DashboardGraphQLQrCodeUsageEnum;
};