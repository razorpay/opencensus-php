import { analyticsTrack } from 'common/utils/analytics';

function _track() {
  let segmentProperty = {};
  function sendTrack(
    objectName,
    actionName = 'clicked',
    screen = 'payments',
    toLumberjack = false,
    properties = {},
  ) {
    return analyticsTrack({
      objectName,
      actionName,
      screen,
      toLumberjack,
      properties: {
        ...segmentProperty,
        ...properties,
      },
    });
  }

  return {
    init: (_segmentProperty) => {
      segmentProperty = _segmentProperty;
    },
    paymentDetails: (objectName, actionName, screen) => {
      sendTrack(objectName, actionName, screen);
    },
    paymentDetailsSidebar: (objectName, actionName, screen) => {
      sendTrack(objectName, actionName, screen);
    },
    paymentDetailsUnmount: (objectName, productType, properties = {}) => {
      sendTrack(objectName, 'clicked', `${productType} Details`, true, {
        location: `${productType}`,
        ...properties,
      });
    },
    settlementOverView: (objectName, productType, properties = {}) => {
      sendTrack(objectName, 'clicked', `${productType} Details`, true, {
        location: `${productType}`,
        ...properties,
      });
    },
    methodViewed: (objectName, productType, properties = {}) => {
      sendTrack(objectName, 'clicked', `${productType} Details`, true, {
        location: `${productType}`,
        ...properties,
      });
    },
    handleSettlementGuide: (objectName, screen) => {
      sendTrack(objectName, 'clicked', screen, true);
    },
    trackContactSupport: (objectName, screen) => {
      sendTrack(objectName, 'clicked', screen, true);
    },
    onCreateTransfer: (objectName, screen) => {
      sendTrack(objectName, 'clicked', screen, true);
    },
    knowMore: (objectName, screen) => {
      sendTrack(objectName, 'clicked', screen, true);
    },
    sameDaySettlement: (objectName, screen) => {
      sendTrack(objectName, 'clicked', screen, true);
    },
    settlementClose: (objectName, screen) => {
      sendTrack(objectName, 'clicked', screen, true);
    },
    capturePayment: (objectName, screen) => {
      sendTrack(objectName, 'clicked', screen, true);
    },
    onActionSideBar: (objectName, screen) => {
      sendTrack(objectName, 'clicked', screen, true);
    },
  };
}

export const trackRefundError = (error, method) => {
  analyticsTrack({
    objectName: 'issue refund',
    actionName: 'response',
    screen: 'payments',
    toLumberjack: true,
    properties: {
      method,
      status: 'error',
      error,
    },
  });
};

export default _track();
