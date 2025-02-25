import { DashboardGraphQLInputMaybe, DashboardGraphQLMerchantClarificationInputType, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMerchantEmailInputField = {
  clarificationReasons?: DashboardGraphQLInputMaybe<Array<DashboardGraphQLMerchantClarificationInputType>>;
  value?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['EmailAddress']>;
};