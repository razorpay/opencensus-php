import { DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLAadhaarCaptchaV2SuccessResponse = {
  __typename?: 'DashboardGraphQLAadhaarCaptchaV2SuccessResponse';
  captcha: DashboardGraphQLScalars['String'];
  code: DashboardGraphQLScalars['PositiveInt'];
  isSessionExpired: DashboardGraphQLScalars['Boolean'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};