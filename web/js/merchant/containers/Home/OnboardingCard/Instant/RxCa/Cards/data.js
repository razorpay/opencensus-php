export const benefits = [
  'Track and automate all your finances',
  'Transfer money 24*7 even on bank holidays',
  'Priority Support and early access to new features',
  'Chequebook and debit cards',
  'Add beneficiaries instantly',
];

export const currentAccountStatuses = {
  created: 'created',
  picked: 'picked',
  initiated: 'initiated',
  processing: 'processing',
  processed: 'processed',
  cancelled: 'cancelled',
  activated: 'activated',
  unserviceable: 'unserviceable',
  rejected: 'rejected',
};

export const analyticsStatusMap = {
  created: 'request_received',
  picked: 'process_started',
  initiated: 'kyc_in_progress',
  processing: 'kyc_in_progress',
  processed: 'activation_in_progress',
  cancelled: 'request_cancelled',
  activated: 'account_activated',
  unserviceable: 'unserviceable',
  rejected: 'request_rejected',
};
