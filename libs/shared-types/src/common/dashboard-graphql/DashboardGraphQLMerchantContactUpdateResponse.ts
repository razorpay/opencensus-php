import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMerchantContact } from './index';
export type DashboardGraphQLMerchantContactUpdateResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLMerchantContactUpdateResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  merchantContact: DashboardGraphQLMerchantContact;
  message: DashboardGraphQLScalars['String'];
  success: DashboardGraphQLScalars['Boolean'];
};