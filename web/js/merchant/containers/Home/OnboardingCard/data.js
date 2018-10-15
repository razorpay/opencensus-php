export const NEEDS_CLARIFICATION = 'needs_clarification';
export const CLARIFICATION_THROUGH_CALL = 'call';
export const CLARIFICATION_THROUGH_EMAIL = 'email';
export const PERSONALISE_URL = '/config';
export const ACTIVATION_URL = '/activation';
export const TEST_MODE = 'test';
export const LIVE_MODE = 'live';

import store from 'merchant/store';
const user = store.getState().getUser();

export const onBoardingItems = [
  'Generate Financial Reports',
  'Check Transaction History',
  'Access API keys  & Webhooks',
  `Access ${user.isOrgRZP() ? 'Razorpay ' : ''}Products`,
  'Check Settlements',
  'Issue Refunds',
];
