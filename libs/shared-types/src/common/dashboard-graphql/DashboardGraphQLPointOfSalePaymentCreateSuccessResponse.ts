import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLPointOfSale } from './index';
export type DashboardGraphQLPointOfSalePaymentCreateSuccessResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLPointOfSalePaymentCreateSuccessResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  pointOfSale: DashboardGraphQLPointOfSale;
  success: DashboardGraphQLScalars['Boolean'];
};