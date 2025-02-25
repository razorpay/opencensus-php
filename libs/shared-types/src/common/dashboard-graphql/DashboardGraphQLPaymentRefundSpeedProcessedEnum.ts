export enum DashboardGraphQLPaymentRefundSpeedProcessedEnum {
  /** Indicates that the refund has been processed instantly via fund transfer. */
  INSTANT = 'INSTANT',
  /** Indicates that the refund has been processed by the payment processing partner. That is, the refund will take 5-7 working days. */
  NORMAL = 'NORMAL',
}