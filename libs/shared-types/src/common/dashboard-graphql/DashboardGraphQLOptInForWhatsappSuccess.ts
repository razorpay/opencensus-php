import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLOptInForWhatsappSuccess = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLOptInForWhatsappSuccess';
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  optinStatus: DashboardGraphQLScalars['Boolean'];
  success: DashboardGraphQLScalars['Boolean'];
};