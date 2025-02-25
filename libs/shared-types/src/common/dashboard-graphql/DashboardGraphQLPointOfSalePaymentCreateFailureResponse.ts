import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLPointOfSalePaymentCreateFailureResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLPointOfSalePaymentCreateFailureResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  success: DashboardGraphQLScalars['Boolean'];
};