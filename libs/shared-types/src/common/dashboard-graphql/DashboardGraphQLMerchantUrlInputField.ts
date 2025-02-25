import { DashboardGraphQLInputMaybe, DashboardGraphQLMerchantClarificationInputType, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMerchantUrlInputField = {
  clarificationReasons?: DashboardGraphQLInputMaybe<Array<DashboardGraphQLMerchantClarificationInputType>>;
  value?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['URL']>;
};