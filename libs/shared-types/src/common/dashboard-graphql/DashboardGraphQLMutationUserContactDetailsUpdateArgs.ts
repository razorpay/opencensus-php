import { DashboardGraphQLInputMaybe, DashboardGraphQLPhoneInput, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMutationUserContactDetailsUpdateArgs = {
  contact?: DashboardGraphQLInputMaybe<DashboardGraphQLPhoneInput>;
  name: DashboardGraphQLScalars['String'];
};