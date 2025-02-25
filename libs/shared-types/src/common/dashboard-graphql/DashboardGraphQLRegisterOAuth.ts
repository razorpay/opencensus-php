import { DashboardGraphQLAuthUser, DashboardGraphQLRegisterOAuthEmailError, DashboardGraphQLRegisterOAuthEmailExist, DashboardGraphQLRegisterOAuthInvalidTokenError } from './index';
export type DashboardGraphQLRegisterOAuth =
  | DashboardGraphQLAuthUser
  | DashboardGraphQLRegisterOAuthEmailError
  | DashboardGraphQLRegisterOAuthEmailExist
  | DashboardGraphQLRegisterOAuthInvalidTokenError;