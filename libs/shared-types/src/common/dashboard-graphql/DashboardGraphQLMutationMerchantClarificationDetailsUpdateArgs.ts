import { DashboardGraphQLInputMaybe, DashboardGraphQLScalars, DashboardGraphQLMerchantCommentTypeEnum, DashboardGraphQLFieldDetailsInput } from './index';
export type DashboardGraphQLMutationMerchantClarificationDetailsUpdateArgs = {
  comment?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  commentType?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantCommentTypeEnum>;
  fieldDetails?: DashboardGraphQLInputMaybe<DashboardGraphQLFieldDetailsInput>;
  fieldName: DashboardGraphQLScalars['String'];
  submitField?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['Boolean']>;
  submitPosField?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['Boolean']>;
};