import { DashboardGraphQLInputMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMutationLoginEmailArgs = {
  captcha?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  captchaMode?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  email: DashboardGraphQLScalars['EmailAddress'];
  password: DashboardGraphQLScalars['String'];
};