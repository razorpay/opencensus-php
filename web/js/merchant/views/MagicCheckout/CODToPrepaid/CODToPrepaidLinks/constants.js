export const PL_STATUS = ['awaited', 'sent', 'failed', 'expired', 'cancelled', 'paid'];

export const PL_NOTIFICATION_MSG = {
  expireSuccess: 'Payment link expired successfully.',
  expireError: 'Something went wrong, please try again after sometime.',
};

export const PL_EXPIRE_CONFIRMATION_TEXT = {
  heading: 'Expire payment link',
  description:
    'You will not able send any other conversion link for this order after it has expired.',
  affirmLabel: 'Expire link',
  abortLabel: "Don't expire",
};

export const PL_STATUSES_MAPPING = {
  sent: 'Conversion link sent',
  paid: 'Converted to Prepaid',
  failed: 'Failed to send link',
  cancelled: 'Manually expired',
  expired: 'Auto expired',
};

export const PAYMENT_STATUS = [
  { label: 'All', name: '' },
  { label: 'Converted to Prepaid', name: 'paid' },
  { label: 'Conversion link sent', name: 'sent' },
  { label: 'Failed to send link', name: 'failed' },
  { label: 'Manually expired', name: 'cancelled' },
  { label: 'Auto expired', name: 'expired' },
];

export const COUNT = [
  { label: '25', name: 25 },
  { label: '15', name: 15 },
  { label: '5', name: 5 },
];
