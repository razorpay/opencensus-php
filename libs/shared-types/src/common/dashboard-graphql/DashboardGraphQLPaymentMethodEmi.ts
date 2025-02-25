import { DashboardGraphQLPaymentMethodCard, DashboardGraphQLPaymentEmiDetails } from './index';
export type DashboardGraphQLPaymentMethodEmi = {
  __typename?: 'DashboardGraphQLPaymentMethodEmi';
  card: DashboardGraphQLPaymentMethodCard;
  emi: DashboardGraphQLPaymentEmiDetails;
};