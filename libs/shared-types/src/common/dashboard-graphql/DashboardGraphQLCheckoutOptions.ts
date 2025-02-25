import { DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLCheckoutOptions = {
  __typename?: 'DashboardGraphQLCheckoutOptions';
  email?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  phone?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
};