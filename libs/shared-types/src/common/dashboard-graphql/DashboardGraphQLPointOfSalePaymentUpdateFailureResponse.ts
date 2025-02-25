import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLPointOfSalePaymentUpdateFailureResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLPointOfSalePaymentUpdateFailureResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  gatewayErrorCode: DashboardGraphQLScalars['String'];
  gatewayErrorDescription: DashboardGraphQLScalars['String'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};