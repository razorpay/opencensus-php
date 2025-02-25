import { DashboardGraphQLScalars, DashboardGraphQLOrganisationEmail, DashboardGraphQLMaybe, DashboardGraphQLOrganisationLogo, DashboardGraphQLOrganisationName } from './index';
export type DashboardGraphQLOrganisation = {
  __typename?: 'DashboardGraphQLOrganisation';
  allowedEmailDomains: Array<DashboardGraphQLScalars['String']>;
  code: DashboardGraphQLScalars['String'];
  domain: DashboardGraphQLScalars['String'];
  email: DashboardGraphQLOrganisationEmail;
  id: DashboardGraphQLScalars['ID'];
  logo?: DashboardGraphQLMaybe<DashboardGraphQLOrganisationLogo>;
  name: DashboardGraphQLOrganisationName;
};