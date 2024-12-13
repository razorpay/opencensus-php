export const newCondition = {
  fact: '',
  operator: 'eq',
  value: '',
} as const;

export const actionTypes = {
  shipping: [
    { type: 'HIDE_FREE_SHIPPING', label: 'Hide Free Shipping Methods' },
    { type: 'HIDE_PAID_SHIPPING', label: 'Hide Paid Shipping Methods' },
    { type: 'SHOW_SPECIFIC_SHIPPING', label: 'Show Specific Shipping Methods' },
    { type: 'HIDE_SPECIFIC_SHIPPING', label: 'Hide Specific Shipping Methods' },
  ],
  payment: [
    { type: 'HIDE_COD', label: 'Hide COD Methods' },
    { type: 'HIDE_PREPAID', label: 'Hide Prepaid Methods' },
    { type: 'SHOW_SPECIFIC_PAYMENT', label: 'Show Specific Payment Methods' },
    { type: 'HIDE_SPECIFIC_PAYMENT', label: 'Hide Specific Payment Methods' },
  ],
};

export const booleanSelectInputOptions = [
  { name: 'true', label: 'True', value: 'true' },
  { name: 'false', label: 'False', value: 'false' },
];

export const LIMITS = {
  MIN_GROUPS_IN_BLOCK: 1,
  MIN_CONDITIONS_IN_GROUP: 1,
};
