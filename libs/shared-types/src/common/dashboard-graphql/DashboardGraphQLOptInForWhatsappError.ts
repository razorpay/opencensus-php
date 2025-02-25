import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLOptInForWhatsappEnum } from './index';
export type DashboardGraphQLOptInForWhatsappError = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLOptInForWhatsappError';
  code: DashboardGraphQLScalars['PositiveInt'];
  errorCode?: DashboardGraphQLMaybe<DashboardGraphQLOptInForWhatsappEnum>;
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};