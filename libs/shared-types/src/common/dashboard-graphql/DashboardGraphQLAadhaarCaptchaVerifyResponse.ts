import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLAadhaarCaptchaVerifyErrorTypeEnum } from './index';
export type DashboardGraphQLAadhaarCaptchaVerifyResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLAadhaarCaptchaVerifyResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  errorCode?: DashboardGraphQLMaybe<DashboardGraphQLAadhaarCaptchaVerifyErrorTypeEnum>;
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};