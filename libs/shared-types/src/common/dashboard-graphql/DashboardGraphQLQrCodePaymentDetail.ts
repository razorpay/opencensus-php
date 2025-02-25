import { DashboardGraphQLMaybe, DashboardGraphQLMoney, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLQrCodePaymentDetail = {
  __typename?: 'QRCodePaymentDetail';
  /** The amount allowed for a transaction. If specified, then any transaction of an amount less than or more than this value is not allowed. */
  paymentAmount?: DashboardGraphQLMaybe<DashboardGraphQLMoney>;
  /** The total amount received on the QR Code. Only captured payments are considered */
  paymentAmountReceived?: DashboardGraphQLMaybe<DashboardGraphQLMoney>;
  /** The total number of captured payments received on the QR Code */
  paymentsReceivedCount?: DashboardGraphQLMaybe<DashboardGraphQLScalars['NonNegativeInt']>;
};