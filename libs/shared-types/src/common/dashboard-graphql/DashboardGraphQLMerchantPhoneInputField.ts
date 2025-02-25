import { DashboardGraphQLInputMaybe, DashboardGraphQLMerchantClarificationInputType, DashboardGraphQLPhoneInput } from './index';
export type DashboardGraphQLMerchantPhoneInputField = {
  clarificationReasons?: DashboardGraphQLInputMaybe<Array<DashboardGraphQLMerchantClarificationInputType>>;
  value?: DashboardGraphQLInputMaybe<DashboardGraphQLPhoneInput>;
};