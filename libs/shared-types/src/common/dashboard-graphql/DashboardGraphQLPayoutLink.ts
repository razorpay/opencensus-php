import { DashboardGraphQLMoney, DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLPayoutLinkDate, DashboardGraphQLMerchantContactFundAccount, DashboardGraphQLMerchantContact, DashboardGraphQLPayout, DashboardGraphQLPayoutLinkSentVia, DashboardGraphQLPayoutLinkStatusEnum, DashboardGraphQLUser } from './index';
export type DashboardGraphQLPayoutLink = {
  __typename?: 'DashboardGraphQLPayoutLink';
  amount: DashboardGraphQLMoney;
  attemptCount: DashboardGraphQLScalars['NonNegativeInt'];
  dates?: DashboardGraphQLMaybe<DashboardGraphQLPayoutLinkDate>;
  description: DashboardGraphQLScalars['String'];
  fundAccount?: DashboardGraphQLMaybe<DashboardGraphQLMerchantContactFundAccount>;
  id: DashboardGraphQLScalars['ID'];
  merchantContact: DashboardGraphQLMerchantContact;
  notes?: DashboardGraphQLMaybe<DashboardGraphQLScalars['JSONObject']>;
  payouts: Array<DashboardGraphQLPayout>;
  purpose: DashboardGraphQLScalars['String'];
  referenceId?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  sentVia: DashboardGraphQLPayoutLinkSentVia;
  shortUrl?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  status: DashboardGraphQLPayoutLinkStatusEnum;
  user?: DashboardGraphQLMaybe<DashboardGraphQLUser>;
};