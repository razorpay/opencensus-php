import { DashboardGraphQLMoneyInput, DashboardGraphQLInputMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMutationOrderCreateArgs = {
  amount: DashboardGraphQLMoneyInput;
  notes?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['JSONObject']>;
  receipt?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
};