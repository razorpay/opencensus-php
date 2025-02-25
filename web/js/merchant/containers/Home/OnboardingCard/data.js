import React from "react";
export const NEEDS_CLARIFICATION = 'needs_clarification';
export const CLARIFICATION_THROUGH_CALL = 'call';
export const CLARIFICATION_THROUGH_EMAIL = 'email';
export const PERSONALISE_URL = '/config';
export const ACTIVATION_URL = '/activation';
export const TEST_MODE = 'test';
export const LIVE_MODE = 'live';

export function onBoardingItems(user) {
  return [
    'Generate Financial Reports',
    'Check Transaction History',
    'Access API keys  & Webhooks',
    `Access ${user.isOrgRZP ? 'Razorpay ' : ''}Products`,
    'Check Settlements',
    'Issue Refunds',
  ];
}

export const rxBenefits = [
  <span className="rx-benefits">
    <span className="highlight">Pricing reduced to 1.85%</span> for all payment methods on payment
    gateway
  </span>,
  <span className="rx-benefits">
    <span className="highlight">Setup & maintenance costs</span> waived off
  </span>,
  <span className="rx-benefits">
    <span className="highlight">500 free payouts per month </span> and reduced pricing for payouts
    on RazorpayX
  </span>,
];

export const rxCaFlag = 'rxCaFlag';
export const rxKYCvisitedFlag = 'rxKYCvisitedFlag';
export const rxHomevisitedFlag = 'rxHomevisitedFlag';
export const rxCaSelectedFlag = 'rxCaSelectedFlag';
export const caReqEventType = 'CURRENT_ACCOUNT_INTEREST';
export const rxCaExp = 'rx_ca_experiment__1';

export const RX_HOTJAR_DATA = {
  CA_HOME: {
    trigger: 'CA-EXP-PG-HOME',
    tags: ['CA experiment PG - Home'],
  },
  CA_KYC: {
    trigger: 'CA-EXP-PG-KYC',
    tags: ['CA experiment PG - KYC'],
  },
};
