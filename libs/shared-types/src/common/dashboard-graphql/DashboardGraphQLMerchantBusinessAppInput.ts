import { DashboardGraphQLInputMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMerchantBusinessAppInput = {
  businessAppPassword?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  businessAppUrl: DashboardGraphQLScalars['URL'];
  businessAppUsername?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
};