import { DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLWhatsappNotificationStatusResponse = {
  __typename?: 'DashboardGraphQLWhatsappNotificationStatusResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  isEnabled?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Boolean']>;
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};