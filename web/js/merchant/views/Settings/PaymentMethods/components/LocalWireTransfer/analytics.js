import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

const track = ({ properties, ...args }) => {
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

export const trackAccountCopied = (currency) => {
  track({
    objectName: 'b2b accounts Copied',
    actionName: 'clicked',
    properties: {
      accountCoppied: true,
      accountName: currency,
    },
  });
};

export const trackTandCPopupOpened = () => {
  track({
    objectName: 'b2b accounts list',
    actionName: 'clicked',
    properties: {
      isActivateClicked: true,
      isPopupOpened: true,
    },
  });
};

export const trackActivateClick = () => {
  track({
    objectName: 'b2b accounts activation popup',
    actionName: 'clicked',
    properties: {
      isTermsAndConditionsAccepted: true,
      activateClicked: true,
      isActivated: false,
    },
  });
};

export const trackAccountActivated = () => {
  track({
    objectName: 'b2b accounts activation',
    actionName: 'response',
    properties: {
      isActivated: true,
      status: 'success',
    },
  });
};

export const trackAccountError = (error) => {
  track({
    objectName: 'b2b accounts activation',
    actionName: 'response',
    properties: {
      errorReason: error,
      isActivated: false,
      status: 'error',
    },
  });
};
