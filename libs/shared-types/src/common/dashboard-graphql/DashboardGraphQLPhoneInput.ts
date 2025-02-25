import { DashboardGraphQLInputMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLPhoneInput = {
  countryCode?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  number: DashboardGraphQLScalars['String'];
};