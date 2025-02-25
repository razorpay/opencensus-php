import { DashboardGraphQLScalars, DashboardGraphQLInputMaybe } from './index';
export type DashboardGraphQLMutationOnboardingPaymentOrderVerifyArgs = {
  orderId: DashboardGraphQLScalars['String'];
  paymentId: DashboardGraphQLScalars['String'];
  paymentType?: DashboardGraphQLInputMaybe<DashboardGraphQLScalars['String']>;
  signature: DashboardGraphQLScalars['String'];
};