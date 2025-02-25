import { DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMerchantFeatureFlag = {
  __typename?: 'DashboardGraphQLMerchantFeatureFlag';
  displayText?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  isEnabled?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Boolean']>;
  name: DashboardGraphQLScalars['String'];
};