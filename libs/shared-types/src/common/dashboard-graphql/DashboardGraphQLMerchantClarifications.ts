import { DashboardGraphQLClarificationComments, DashboardGraphQLMerchantClarificationFieldValues, DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLMerchantClarificationStatusEnum } from './index';
export type DashboardGraphQLMerchantClarifications = {
  __typename?: 'DashboardGraphQLMerchantClarifications';
  comments: Array<DashboardGraphQLClarificationComments>;
  fieldValues: DashboardGraphQLMerchantClarificationFieldValues;
  fields: Array<DashboardGraphQLScalars['String']>;
  ncCount?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Int']>;
  status: DashboardGraphQLMerchantClarificationStatusEnum;
};