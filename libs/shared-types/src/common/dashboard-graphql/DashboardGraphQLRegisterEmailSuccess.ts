import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLRegisterEmailSuccess = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLRegisterEmailSuccess';
  code: DashboardGraphQLScalars['PositiveInt'];
  email: DashboardGraphQLScalars['EmailAddress'];
  merchantId: DashboardGraphQLScalars['ID'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  name: DashboardGraphQLScalars['String'];
  success: DashboardGraphQLScalars['Boolean'];
  token?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  userId: DashboardGraphQLScalars['ID'];
};