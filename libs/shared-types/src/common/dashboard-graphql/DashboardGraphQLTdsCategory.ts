import { DashboardGraphQLScalars } from './index';
export type DashboardGraphQLTdsCategory = {
  __typename?: 'TDSCategory';
  code: DashboardGraphQLScalars['String'];
  id: DashboardGraphQLScalars['Int'];
  name: DashboardGraphQLScalars['String'];
  rate: DashboardGraphQLScalars['Float'];
};