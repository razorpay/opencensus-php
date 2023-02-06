export const RULE_TYPE_OPTIONS = [
  { label: 'Select RTO risk', name: '' },
  { label: 'High risk', name: 'high' },
  { label: 'Medium risk', name: 'medium' },
  { label: 'Low risk', name: 'low' },
];

export const RULE_ACTION_OPTIONS = [
  { label: 'Select action', name: '' },
  { label: 'Cancel the order', name: 'cancel' },
  { label: 'Put the order on hold', name: 'hold' },
  { label: 'Approve the order', name: 'approve' },
];

export const RULE_TYPES = ['high', 'medium', 'low'];

export const RULE_ACTIONS = {
  cancel: 'Cancel the order',
  hold: 'Put the order on hold',
  approve: 'Approve the order',
};

export const CONFIRMATION_MODAL_TEXTS = {
  heading: 'Remove COD workflow?',
  description: 'Are you sure you want to remove all the COD review workflow conditions?',
  primaryLabel: 'Yes, Remove',
  secondaryLabel: 'Don’t Remove',
};
