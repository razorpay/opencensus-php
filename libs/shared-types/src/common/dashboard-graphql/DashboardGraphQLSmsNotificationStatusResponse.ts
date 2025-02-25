import { DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLSmsNotificationStatusResponse = {
  __typename?: 'DashboardGraphQLSmsNotificationStatusResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  isEnabled: DashboardGraphQLScalars['Boolean'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};