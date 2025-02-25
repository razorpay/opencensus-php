import { DashboardGraphQLScalars, DashboardGraphQLPayoutPurposeTypeEnum } from './index';
export type DashboardGraphQLPayoutPurpose = {
  __typename?: 'DashboardGraphQLPayoutPurpose';
  label: DashboardGraphQLScalars['String'];
  type: DashboardGraphQLPayoutPurposeTypeEnum;
};