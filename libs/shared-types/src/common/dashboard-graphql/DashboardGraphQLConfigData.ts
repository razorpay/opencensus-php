import { DashboardGraphQLMaybe, DashboardGraphQLScalars, DashboardGraphQLUpiTerminalProcurementStatusEnum } from './index';
export type DashboardGraphQLConfigData = {
  __typename?: 'DashboardGraphQLConfigData';
  /** count of bvs bank verification attempt */
  bankAccountVerificationAttemptCount?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Int']>;
  /** count of company pan verification attempt */
  companyPanVerificationAttemptCount?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Int']>;
  couponPopupCount?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Int']>;
  /** Indicates visibility of mtu transaction Popup */
  isMtuCouponAvailable: DashboardGraphQLScalars['Boolean'];
  /** Indicates visibility of mtu congratulatory Popup */
  isMtuCouponCongratulatoryPopupEnabled: DashboardGraphQLScalars['Boolean'];
  /** Indicates whether merchant has signed up through referral and not yet done mtu */
  isSignedUpReferee?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Boolean']>;
  /** count of promoter pan verification attempt */
  promoterPanVerificationAttemptCount?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Int']>;
  /** DashboardGraphQLMerchant names of friends who completed the referral process successfully since modal is last seen by advocate */
  refereeNames?: DashboardGraphQLMaybe<Array<DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>>>;
  /** DashboardGraphQLAmount credit (in paisa) to for each successful referral */
  referralAmount?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Int']>;
  /** Count of referral success popup - defaults to zero */
  referralSuccessPopupCount?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Int']>;
  /** Total referrals done by advocate so far */
  referredCount?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Int']>;
  /** Indicates visibility of ftux final screen */
  showFtuxFinalScreen?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Boolean']>;
  /** Indicates visibility of first time payment success banner for ftux */
  showFtuxFirstPaymentBanner?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Boolean']>;
  upiTerminalProcurementStatus?: DashboardGraphQLMaybe<DashboardGraphQLUpiTerminalProcurementStatusEnum>;
  /** Count of website compliance skips */
  websiteIncompleteSoftNudgeCount?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Int']>;
};