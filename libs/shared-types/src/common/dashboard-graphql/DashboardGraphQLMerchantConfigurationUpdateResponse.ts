import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMerchantConfigurationUpdateResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'merchantConfigurationUpdateResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  message: DashboardGraphQLScalars['String'];
  success: DashboardGraphQLScalars['Boolean'];
};