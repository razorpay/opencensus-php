import { DashboardGraphQLMaybe, DashboardGraphQLModularOnboardingField, DashboardGraphQLModularComponentMeta, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLModularComponent = {
  __typename?: 'DashboardGraphQLModularComponent';
  fields?: DashboardGraphQLMaybe<Array<DashboardGraphQLMaybe<DashboardGraphQLModularOnboardingField>>>;
  meta?: DashboardGraphQLMaybe<DashboardGraphQLModularComponentMeta>;
  name: DashboardGraphQLScalars['String'];
  progress: DashboardGraphQLScalars['Float'];
  status: DashboardGraphQLScalars['String'];
};