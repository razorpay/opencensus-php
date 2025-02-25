import { DashboardGraphQLScalars, DashboardGraphQLInputMaybe } from './index';
export type DashboardGraphQLQueryMerchantPreferencesArgs = {
  preferenceGroup: DashboardGraphQLScalars['String'];
  preferenceType?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
};