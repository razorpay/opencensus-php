import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMerchantInstrument } from './index';
export type DashboardGraphQLMerchantInstrumentCancelRequestMutationSucessResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLMerchantInstrumentCancelRequestMutationSucessResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  merchantInstrument: DashboardGraphQLMerchantInstrument;
  message: DashboardGraphQLScalars['String'];
  success: DashboardGraphQLScalars['Boolean'];
};