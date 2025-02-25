import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLNotificationWhatsAppOptIn = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLNotificationWhatsAppOptIn';
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};