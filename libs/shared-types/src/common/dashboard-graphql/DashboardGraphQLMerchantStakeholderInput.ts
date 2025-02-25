import { DashboardGraphQLInputMaybe, DashboardGraphQLScalars, DashboardGraphQLMerchantStringInputField } from './index';
export type DashboardGraphQLMerchantStakeholderInput = {
  isAadharLinked?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['Boolean']>;
  name?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantStringInputField>;
  pan?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantStringInputField>;
};