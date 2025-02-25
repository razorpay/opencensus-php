import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLPaymentLink } from './index';
export type DashboardGraphQLPaymentLinkCreateResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'DashboardGraphQLPaymentLinkCreateResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  paymentLink: DashboardGraphQLPaymentLink;
  success: DashboardGraphQLScalars['Boolean'];
};