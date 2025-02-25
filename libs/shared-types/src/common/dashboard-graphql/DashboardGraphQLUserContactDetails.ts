import { DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLUserContactDetails = {
  __typename?: 'DashboardGraphQLUserContactDetails';
  contact?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  email?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
};