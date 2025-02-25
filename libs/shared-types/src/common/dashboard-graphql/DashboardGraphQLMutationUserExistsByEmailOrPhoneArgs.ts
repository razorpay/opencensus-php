import { DashboardGraphQLInputMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMutationUserExistsByEmailOrPhoneArgs = {
  contact_mobile?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  email?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
};