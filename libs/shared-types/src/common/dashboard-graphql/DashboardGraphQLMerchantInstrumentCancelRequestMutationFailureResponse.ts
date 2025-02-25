import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMerchantInstrumentCancelRequestMutationFailureResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLMerchantInstrumentCancelRequestMutationFailureResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  message: DashboardGraphQLScalars['String'];
  success: DashboardGraphQLScalars['Boolean'];
};