import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMerchantInstrument, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLMerchantInstrumentReInitiateSuccessResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLMerchantInstrumentReInitiateSuccessResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  merchantInstrument: DashboardGraphQLMerchantInstrument;
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};