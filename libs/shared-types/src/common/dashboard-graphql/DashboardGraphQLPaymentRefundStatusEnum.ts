export enum DashboardGraphQLPaymentRefundStatusEnum {
  CREATED = 'CREATED',
  FAILED = 'FAILED',
  PENDING = 'PENDING',
  /** This is the terminal state of the refund */
  PROCESSED = 'PROCESSED',
  /** Indicates that Razorpay is attempting to process the refund */
  PROCESSING = 'PROCESSING',
}