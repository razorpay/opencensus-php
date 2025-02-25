import { DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLAddressByPincodeSuccessResponse = {
  __typename?: 'DashboardGraphQLAddressByPincodeSuccessResponse';
  city: DashboardGraphQLScalars['String'];
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  state: DashboardGraphQLScalars['String'];
  stateCode: DashboardGraphQLScalars['String'];
  success: DashboardGraphQLScalars['Boolean'];
};