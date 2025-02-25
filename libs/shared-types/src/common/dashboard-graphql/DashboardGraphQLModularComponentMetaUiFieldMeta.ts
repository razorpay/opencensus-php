import { DashboardGraphQLMaybe, DashboardGraphQLScalars, DashboardGraphQLModularOnboardingOption, DashboardGraphQLModularComponentMetUiFieldMetaRedirectUrl } from './index';
export type DashboardGraphQLModularComponentMetaUiFieldMeta = {
  __typename?: 'DashboardGraphQLModularComponentMetaUiFieldMeta';
  accessibilityLabel?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  dataType: DashboardGraphQLScalars['String'];
  defaultValue?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  description?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  options?: DashboardGraphQLMaybe<Array<DashboardGraphQLMaybe<DashboardGraphQLModularOnboardingOption>>>;
  redirectUrl?: DashboardGraphQLMaybe<DashboardGraphQLModularComponentMetUiFieldMetaRedirectUrl>;
  selectionType?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  size?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  title?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
};