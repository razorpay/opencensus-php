import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLPayoutCreateIciciResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLPayoutCreateIciciResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  payoutId?: DashboardGraphQLMaybe<DashboardGraphQLScalars['ID']>;
  success: DashboardGraphQLScalars['Boolean'];
};