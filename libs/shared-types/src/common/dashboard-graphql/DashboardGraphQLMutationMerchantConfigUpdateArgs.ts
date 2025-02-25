import { DashboardGraphQLInputMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMutationMerchantConfigUpdateArgs = {
  couponPopupCount?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['PositiveInt']>;
  namespace: DashboardGraphQLScalars['String'];
  referralSuccessPopupCount?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['NonNegativeInt']>;
};