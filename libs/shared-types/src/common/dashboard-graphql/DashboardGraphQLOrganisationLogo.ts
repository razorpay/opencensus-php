import { DashboardGraphQLMaybe, DashboardGraphQLImage } from './index';
export type DashboardGraphQLOrganisationLogo = {
  __typename?: 'DashboardGraphQLOrganisationLogo';
  header?: DashboardGraphQLMaybe<DashboardGraphQLImage>;
  invoice?: DashboardGraphQLMaybe<DashboardGraphQLImage>;
  login?: DashboardGraphQLMaybe<DashboardGraphQLImage>;
};