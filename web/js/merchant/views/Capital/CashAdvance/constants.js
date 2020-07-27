export const VIEWS = {
  WITHDRAW: 'WITHDRAW',
  WITHDRAW_SUCCESS: 'WITHDRAW_SUCCESS',
  WITHDRAW_FAIL: 'WITHDRAW_FAIL',
};

export const STATUSES = {
  CREATED: 'CREATED',
  INITIATED: 'INITIATED',
  PENDING: 'PENDING',
  PARTIALLY_PROCESSED: 'PARTIALLY_PROCESSED',
  PROCESSED: 'PROCESSED',
  REJECTED: 'REJECTED',
  FAILED: 'FAILED',
  PARTIALLY_REPAID: 'PARTIALLY_REPAID',
  REPAID: 'REPAID',
  MANUALLY_PROCESSED: 'MANUALLY_PROCESSED',
};

export const StatusPillClasses = {
  [STATUSES.CREATED]: 'bg-light',
  [STATUSES.INITIATED]: 'bg-light',
  [STATUSES.PENDING]: 'bg-warning',
  [STATUSES.PARTIALLY_PROCESSED]: 'bg-info',
  [STATUSES.PROCESSED]: 'bg-info',
  [STATUSES.REJECTED]: 'bg-danger',
  [STATUSES.FAILED]: 'bg-danger',
  [STATUSES.PARTIALLY_REPAID]: 'bg-light',
  [STATUSES.REPAID]: 'bg-success',
  [STATUSES.MANUALLY_PROCESSED]: 'bg-light',
};

export const STATUS_DESCRIPTIONS = {
  [STATUSES.INITIATED]:
    'Withdrawal request has been created, and yet to be' + ' shared with bank.',
  [STATUSES.PROCESSED]:
    'The amount has been successfully disbursed to your' + ' bank account.',
  [STATUSES.INITIATED]:
    'The disbursal of the requested withdrawal is pending' + ' on the bank.',
  [STATUSES.CREATED]:
    'The withdrawal request has been successfully sent to' + ' the bank.',
  [STATUSES.FAILED]: 'Transfer didn’t happen for some reason on the bank side.',
  [STATUSES.REJECTED]:
    'Transfer didn’t happen for some reason on the bank side.',
  [STATUSES.PARTIALLY_REPAID]:
    'Some part of the repayment has been only collected.',
  [STATUSES.REPAID]:
    'The entire repayment amount has been successfully repaid.',
};

export const STATUS_LABELS = {
  [STATUSES.CREATED]: 'Requested',
  [STATUSES.INITIATED]: 'Requested',
  [STATUSES.PARTIALLY_PROCESSED]: 'Partially Processed',
  [STATUSES.MANUALLY_PROCESSED]: 'Processed',
  [STATUSES.PROCESSED]: 'Processed',
  [STATUSES.REJECTED]: 'Rejected',
  [STATUSES.FAILED]: 'Failed',
  [STATUSES.PARTIALLY_REPAID]: 'Partially Repaid',
  [STATUSES.REPAID]: 'Repaid',
};

export const STATUS_FILTER_OPTIONS = {
  [STATUSES.CREATED]: 'Created',
  [STATUSES.INITIATED]: STATUS_LABELS[[STATUSES.INITIATED]],
  [STATUSES.PARTIALLY_PROCESSED]: STATUS_LABELS[[STATUSES.PARTIALLY_PROCESSED]],
  [STATUSES.PROCESSED]: STATUS_LABELS[[STATUSES.PROCESSED]],
  [STATUSES.REJECTED]: STATUS_LABELS[[STATUSES.REJECTED]],
  [STATUSES.FAILED]: STATUS_LABELS[[STATUSES.FAILED]],
  [STATUSES.PARTIALLY_REPAID]: STATUS_LABELS[[STATUSES.PARTIALLY_REPAID]],
  [STATUSES.REPAID]: STATUS_LABELS[[STATUSES.REPAID]],
};

export const ESTIMATED_AMOUNT_REQUIREMENTS = {
  R100K_TO_500K: '1,00,000 to 5,00,000',
  R500K_TO_1000K: '5,00,000 to 10,00,000',
  R50K_TO_100K: '50,000 to 1,00,000',
  RLESS_THAN_50K: 'Less than 50,000',
  RMORE_THAN_1000K: 'More than 10,00,000',
};

export const CLOSE_OPTIONS = [
  'Need more guidance with feature',
  'Pricing is too High',
  'I want to automate this withdrawal',
  'Other reasons',
];
