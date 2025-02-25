import { DashboardGraphQLMaybe, DashboardGraphQLClarificationComment, DashboardGraphQLScalars, DashboardGraphQLMerchantClarificationFieldValues, DashboardGraphQLMerchantClarificationFromEnum, DashboardGraphQLMerchantClarificationStatusEnum } from './index';
export type DashboardGraphQLClarificationComments = {
  __typename?: 'DashboardGraphQLClarificationComments';
  comment?: DashboardGraphQLMaybe<DashboardGraphQLClarificationComment>;
  createdAt: DashboardGraphQLScalars['PositiveInt'];
  fieldDetails?: DashboardGraphQLMaybe<DashboardGraphQLMerchantClarificationFieldValues>;
  messageFrom: DashboardGraphQLMerchantClarificationFromEnum;
  ncCount: DashboardGraphQLScalars['Int'];
  status: DashboardGraphQLMerchantClarificationStatusEnum;
};