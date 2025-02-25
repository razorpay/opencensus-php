import { DashboardGraphQLInputMaybe, DashboardGraphQLMerchantClarificationInputType, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMerchantStringInputField = {
  clarificationReasons?: DashboardGraphQLInputMaybe<Array<DashboardGraphQLMerchantClarificationInputType>>;
  value?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
};