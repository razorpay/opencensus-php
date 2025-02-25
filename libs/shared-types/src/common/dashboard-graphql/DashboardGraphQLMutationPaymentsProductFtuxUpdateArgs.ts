import { DashboardGraphQLScalars, DashboardGraphQLInputMaybe } from './index';
export type DashboardGraphQLMutationPaymentsProductFtuxUpdateArgs = {
  isFtuxComplete: DashboardGraphQLScalars['Boolean'];
  isNewLaunch?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['Boolean']>;
  product: DashboardGraphQLScalars['String'];
};