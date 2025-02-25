import { DashboardGraphQLMaybe, DashboardGraphQLModularComponentMetaUiFieldMeta, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLModularComponentMetaUiField = {
  __typename?: 'DashboardGraphQLModularComponentMetaUiField';
  meta?: DashboardGraphQLMaybe<DashboardGraphQLModularComponentMetaUiFieldMeta>;
  name: DashboardGraphQLScalars['String'];
  value: DashboardGraphQLScalars['String'];
};