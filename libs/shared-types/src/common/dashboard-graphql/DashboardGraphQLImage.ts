import { DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLImage = {
  __typename?: 'DashboardGraphQLImage';
  alt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  description?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  src: DashboardGraphQLScalars['URL'];
};