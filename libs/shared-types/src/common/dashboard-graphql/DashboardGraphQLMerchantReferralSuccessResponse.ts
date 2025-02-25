import { DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLMoney } from './index';
export type DashboardGraphQLMerchantReferralSuccessResponse = {
  __typename?: 'DashboardGraphQLMerchantReferralSuccessResponse';
  /** Indicates whether the advocate is eligible for referring or not */
  canRefer: DashboardGraphQLScalars['Boolean'];
  /** Maximum number of times referral amount will be credited for advocate */
  maxAllowedReferrals?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Int']>;
  /** DashboardGraphQLAmount credit (in paisa) to for each successful referral */
  referralAmount?: DashboardGraphQLMaybe<DashboardGraphQLMoney>;
  /** Shareable referral link for a merchant to refer another merchant */
  referralLink?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
};