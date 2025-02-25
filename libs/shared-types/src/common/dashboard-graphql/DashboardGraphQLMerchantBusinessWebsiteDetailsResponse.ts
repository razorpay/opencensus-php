import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMerchantBusinessWebsiteDetailsResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLMerchantBusinessWebsiteDetailsResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  message: DashboardGraphQLScalars['String'];
  success: DashboardGraphQLScalars['Boolean'];
};