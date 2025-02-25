import { DashboardGraphQLScalars } from './index';
export type DashboardGraphQLAadhaarCaptchaResponse = {
  __typename?: 'DashboardGraphQLAadhaarCaptchaResponse';
  captcha: DashboardGraphQLScalars['String'];
  isSessionExpired: DashboardGraphQLScalars['Boolean'];
};