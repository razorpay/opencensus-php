import { DashboardGraphQLInputMaybe, DashboardGraphQLMerchantClarificationInputType, DashboardGraphQLMerchantBusinessTypeEnum } from './index';
export type DashboardGraphQLMerchantBusinessTypeInputField = {
  clarificationReasons?: DashboardGraphQLInputMaybe<Array<DashboardGraphQLMerchantClarificationInputType>>;
  value?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantBusinessTypeEnum>;
};