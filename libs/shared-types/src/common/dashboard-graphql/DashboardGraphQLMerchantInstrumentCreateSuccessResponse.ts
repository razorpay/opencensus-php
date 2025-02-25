import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMerchantInstrument } from './index';
export type DashboardGraphQLMerchantInstrumentCreateSuccessResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLMerchantInstrumentCreateSuccessResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  merchantInstrument: DashboardGraphQLMerchantInstrument;
  message: DashboardGraphQLScalars['String'];
  success: DashboardGraphQLScalars['Boolean'];
};