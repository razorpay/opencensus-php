import { DashboardGraphQLMaybe, DashboardGraphQLModularComponentMetaUiField } from './index';
export type DashboardGraphQLModularComponentMetaUi = {
  __typename?: 'DashboardGraphQLModularComponentMetaUi';
  fields?: DashboardGraphQLMaybe<Array<DashboardGraphQLMaybe<DashboardGraphQLModularComponentMetaUiField>>>;
};