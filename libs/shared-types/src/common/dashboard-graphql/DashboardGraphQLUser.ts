import { DashboardGraphQLMaybe, DashboardGraphQLScalars, DashboardGraphQLMerchant, DashboardGraphQLPhone, DashboardGraphQLUserRole, DashboardGraphQLUserSignupCampaignEnum, DashboardGraphQLUserSignupMethodEnum } from './index';
export type DashboardGraphQLUser = {
  __typename?: 'DashboardGraphQLUser';
  email?: DashboardGraphQLMaybe<DashboardGraphQLScalars['EmailAddress']>;
  id: DashboardGraphQLScalars['ID'];
  isAccountLocked: DashboardGraphQLScalars['Boolean'];
  isAccountVerified: DashboardGraphQLScalars['Boolean'];
  isContactNumberVerified: DashboardGraphQLScalars['Boolean'];
  isEmailVerified: DashboardGraphQLScalars['Boolean'];
  isSignUpViaEmail: DashboardGraphQLScalars['Boolean'];
  isTwoFactorEnabled: DashboardGraphQLScalars['Boolean'];
  isTwoFactorEnforced: DashboardGraphQLScalars['Boolean'];
  /** @deprecated Use user.roles.merchant */
  merchants: Array<DashboardGraphQLMerchant>;
  name?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  phone?: DashboardGraphQLMaybe<DashboardGraphQLPhone>;
  roles: Array<DashboardGraphQLUserRole>;
  signupCampaign?: DashboardGraphQLMaybe<DashboardGraphQLUserSignupCampaignEnum>;
  signupMethod?: DashboardGraphQLMaybe<DashboardGraphQLUserSignupMethodEnum>;
};