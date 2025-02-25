import { DashboardGraphQLScalars } from './index';
export type DashboardGraphQLOrganisationEmail = {
  __typename?: 'DashboardGraphQLOrganisationEmail';
  from: DashboardGraphQLScalars['EmailAddress'];
  to: DashboardGraphQLScalars['EmailAddress'];
};