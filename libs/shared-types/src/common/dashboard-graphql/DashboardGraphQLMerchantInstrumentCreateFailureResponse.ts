import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMerchantInstrumentCreateFailureResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLMerchantInstrumentCreateFailureResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  message: DashboardGraphQLScalars['String'];
  success: DashboardGraphQLScalars['Boolean'];
};