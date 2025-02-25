import { DashboardGraphQLScalars, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLMerchantPaymentHandle = {
  __typename?: 'DashboardGraphQLMerchantPaymentHandle';
  paymentHandleSlug: DashboardGraphQLScalars['String'];
  /** @deprecated paymentPageId is deprecated */
  paymentPageId?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  title: DashboardGraphQLScalars['String'];
  url: DashboardGraphQLScalars['URL'];
};