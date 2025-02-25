import { DashboardGraphQLScalars } from './index';
export type DashboardGraphQLMerchantPaymentHandleSuggestionsResponse = {
  __typename?: 'DashboardGraphQLMerchantPaymentHandleSuggestionsResponse';
  /** Suggestions for the payment handle a merchant can use */
  suggestions: Array<DashboardGraphQLScalars['String']>;
};