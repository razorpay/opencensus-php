import { DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLPageItem } from './index';
export type DashboardGraphQLPaymentPageItem = {
  __typename?: 'DashboardGraphQLPaymentPageItem';
  entity: DashboardGraphQLScalars['String'];
  hsnCode?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  id: DashboardGraphQLScalars['ID'];
  imageUrl?: DashboardGraphQLMaybe<DashboardGraphQLScalars['URL']>;
  item?: DashboardGraphQLMaybe<DashboardGraphQLPageItem>;
  mandatory?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Boolean']>;
  minPurchase?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Int']>;
  paymentLinkId: DashboardGraphQLScalars['ID'];
  planId?: DashboardGraphQLMaybe<DashboardGraphQLScalars['ID']>;
  quantitySold?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Int']>;
  stock?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Int']>;
};