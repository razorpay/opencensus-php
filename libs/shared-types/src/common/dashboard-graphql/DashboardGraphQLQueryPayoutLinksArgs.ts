import { DashboardGraphQLInputMaybe, DashboardGraphQLScalars, DashboardGraphQLPayoutLinkStatusEnum } from './index';
export type DashboardGraphQLQueryPayoutLinksArgs = {
  contactId?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  fromDate?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['DateTime']>;
  fundAccountId?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  id?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['ID']>;
  limit?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['PositiveInt']>;
  offset?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['NonNegativeInt']>;
  status?: DashboardGraphQLInputMaybe<DashboardGraphQLPayoutLinkStatusEnum>;
  toDate?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['DateTime']>;
};