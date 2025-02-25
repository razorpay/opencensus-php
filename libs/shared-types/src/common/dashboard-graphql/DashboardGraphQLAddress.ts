import { DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLAddress = {
  __typename?: 'DashboardGraphQLAddress';
  city?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  country?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  id: DashboardGraphQLScalars['ID'];
  isPrimary?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Boolean']>;
  line1?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  line2?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  state?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  zipcode?: DashboardGraphQLMaybe<DashboardGraphQLScalars['PositiveInt']>;
};