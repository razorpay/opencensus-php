import { DashboardGraphQLOrderAmount, DashboardGraphQLOrderDate, DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLOrderStatusEnum } from './index';
export type DashboardGraphQLOrder = {
  __typename?: 'DashboardGraphQLOrder';
  amount: DashboardGraphQLOrderAmount;
  dates: DashboardGraphQLOrderDate;
  id: DashboardGraphQLScalars['ID'];
  notes?: DashboardGraphQLMaybe<DashboardGraphQLScalars['JSONObject']>;
  paymentAttempts: DashboardGraphQLScalars['NonNegativeInt'];
  receipt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  status: DashboardGraphQLOrderStatusEnum;
};