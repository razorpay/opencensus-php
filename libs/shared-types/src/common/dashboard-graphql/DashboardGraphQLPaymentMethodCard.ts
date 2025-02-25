import { DashboardGraphQLMaybe, DashboardGraphQLPaymentMethodCardCategoryEnum, DashboardGraphQLPaymentMethodCardExpiry, DashboardGraphQLScalars, DashboardGraphQLPaymentMethodCardType } from './index';
export type DashboardGraphQLPaymentMethodCard = {
  __typename?: 'DashboardGraphQLPaymentMethodCard';
  category?: DashboardGraphQLMaybe<DashboardGraphQLPaymentMethodCardCategoryEnum>;
  expiry: DashboardGraphQLPaymentMethodCardExpiry;
  id: DashboardGraphQLScalars['ID'];
  isEmi?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Boolean']>;
  isInternational?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Boolean']>;
  issuer?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  lastFourDigits: DashboardGraphQLScalars['Int'];
  name?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  network: DashboardGraphQLScalars['String'];
  type: DashboardGraphQLPaymentMethodCardType;
};