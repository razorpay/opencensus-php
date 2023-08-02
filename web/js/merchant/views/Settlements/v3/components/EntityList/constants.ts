export const CREDIT_ENTITY_COLUMNS = {
  payment: ['Date', 'Payment ID', 'Gross amount', 'Deductions', 'Net amount', ''],
  reversal: ['Date', 'Reversal ID', 'Gross amount', 'Deductions', 'Net amount', ''],
  adjustment: ['Date', 'Adjustment ID', 'Gross amount', 'Deductions', 'Net amount', ''],
};

export const DEBIT_ENTITY_COLUMNS = {
  refund: ['Date', 'Refund ID', 'Gross amount', 'Deductions', 'Net deduction', ''],
  transfer: ['Date', 'Transfer ID', 'Gross amount', 'Deductions', 'Net deduction', ''],
  dispute: ['Date', 'Dispute ID', 'Gross amount', 'Deductions', 'Net deduction', ''],
  'settlement.ondemand': [
    'Date',
    'Settlement ID',
    'Gross amount',
    'Deductions',
    'Net deduction',
    '',
  ],
  fund: ['Date', 'Fund ID', 'Gross amount', 'Deductions', 'Net deduction', ''],
  credit: ['Date', 'Credit repayment ID', 'Gross amount', 'Deductions', 'Net deduction', ''],
};

export const DEFAULT_CREDIT_ENTITY_COLUMN = [
  'Date',
  'ID',
  'Gross amount',
  'Deductions',
  'Net amount',
  '',
];

export const DEFAULT_DEBIT_ENTITY_COLUMN = [
  'Date',
  'ID',
  'Gross amount',
  'Deductions',
  'Net deduction',
  '',
];
export const tooltipConfig = {
  'Net amount':
    'Amount added to your account. Calculated as the difference between Gross amount and deductions (Gross amount - Deduction)',
  'Net deduction':
    'Amount deducted from your account. Calculated as the sum of Gross amount and deductions (Gross amount + Deduction)',
};

export const keys = ['date', 'entity_id', 'gross_amount', 'deductions', 'net_value', 'id'];

export const SECTION_TAB_MAPPING = {
  gross_settlements: ['payment', 'reversal', 'adjustment'],
  deductions: [
    'ondemand settlement',
    'reversal',
    'adjustment',
    'transfer',
    'refund',
    'dispute',
    'fund',
    'settlement.ondemand',
    'credit',
  ],
};

export const mobileColumns = ['Date', 'Net amount', 'Net deduction', ''];
