export enum DashboardGraphQLPaymentRefundSpeedRequestedEnum {
  /** Indicates that the refund will be processed via the normal speed. That is, the refund will take 5-7 working days */
  NORMAL = 'NORMAL',
  /** Indicates that the refund will be processed at an optimal speed based on Razorpay's internal fund transfer logic */
  OPTIMUM = 'OPTIMUM',
}