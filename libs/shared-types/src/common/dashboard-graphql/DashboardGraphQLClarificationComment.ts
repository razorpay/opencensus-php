import { DashboardGraphQLScalars, DashboardGraphQLMerchantCommentTypeEnum } from './index';
export type DashboardGraphQLClarificationComment = {
  __typename?: 'DashboardGraphQLClarificationComment';
  text: DashboardGraphQLScalars['String'];
  type: DashboardGraphQLMerchantCommentTypeEnum;
};