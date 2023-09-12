import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

const track = ({ properties = {}, ...args }) => {
  analyticsTrack({
    objectName: 'b2b accounts',
    screen: 'settings',
    ...args,
    properties: {
      location: 'Payment Methods',
      ...getCommonAnalyticsProperties(window.rzp_user),
      ...properties,
    },
  });
};

export const trackAccountCopied = (currency: string): void => {
  track({
    objectName: 'b2b accounts Copied',
    actionName: 'clicked',
    properties: {
      accountCoppied: true,
      accountName: currency,
    },
  });
};

export const trackTandCPopupOpened = (account: string): void => {
  track({
    objectName: 'b2b accounts list',
    actionName: 'clicked',
    properties: {
      isActivateClicked: true,
      isPopupOpened: true,
      account,
    },
  });
};

export const trackActivateClick = (account: string, isTnCAccepted = true): void => {
  track({
    objectName: `b2b accounts ${account} activation popup`,
    actionName: 'clicked',
    properties: {
      isTermsAndConditionsAccepted: isTnCAccepted,
      activateClicked: true,
      isActivated: false,
    },
  });
};

export const trackAccountActivated = (account: string): void => {
  track({
    objectName: `b2b accounts ${account} activation`,
    actionName: 'response',
    properties: {
      isActivated: true,
      status: 'success',
    },
  });
};

export const trackAccountError = (error: string, account: string): void => {
  track({
    objectName: `b2b accounts ${account} activation`,
    actionName: 'response',
    properties: {
      errorReason: error,
      isActivated: false,
      status: 'error',
    },
  });
};

export const trackCheckBalanceClicked = (): void => {
  track({
    objectName: 'b2b accounts check balance',
    actionName: 'click',
  });
};

export const trackCheckBalanceFailed = (): void => {
  track({
    objectName: 'b2b accounts check balance',
    actionName: 'response',
    properties: {
      status: 'failed',
    },
  });
};

export const trackSubmitPayoutRequest = (): void => {
  track({
    objectName: 'b2b accounts submit payout',
    actionName: 'request',
    properties: {
      status: 'init',
    },
  });
};

export const trackSubmitPayoutSuccess = (): void => {
  track({
    objectName: 'b2b accounts submit payout',
    actionName: 'response',
    properties: {
      status: 'success',
    },
  });
};

export const trackSubmitPayoutFailed = (): void => {
  track({
    objectName: 'b2b accounts submit payout',
    actionName: 'response',
    properties: {
      status: 'failed',
    },
  });
};

export const trackWithdrawClicked = (): void => {
  track({
    objectName: 'b2b accounts withdraw money',
    actionName: 'click',
  });
};
