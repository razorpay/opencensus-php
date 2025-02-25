import { DashboardGraphQLMaybe, DashboardGraphQLScalars, DashboardGraphQLModularOnboardingOption } from './index';
export type DashboardGraphQLModularOnboardingFieldMeta = {
  __typename?: 'DashboardGraphQLModularOnboardingFieldMeta';
  accessibilityLabel?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  dataType: DashboardGraphQLScalars['String'];
  defaultValue?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  description?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  options?: DashboardGraphQLMaybe<Array<DashboardGraphQLMaybe<DashboardGraphQLModularOnboardingOption>>>;
  selectionType?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  size?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  template?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  title?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  validations?: DashboardGraphQLMaybe<Array<DashboardGraphQLMaybe<DashboardGraphQLScalars['JSONObject']>>>;
};