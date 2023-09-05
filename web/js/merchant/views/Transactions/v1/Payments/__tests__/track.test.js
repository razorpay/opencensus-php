import '@testing-library/jest-dom/extend-expect';
import track from 'merchant/views/Transactions/v1/Payments/track';
import { analyticsTrack } from 'common/utils/analytics';

describe('Tracking', () => {
  const {
    init,
    paymentDetails,
    paymentDetailsSidebar,
    paymentDetailsUnmount,
    settlementOverView,
    methodViewed,
    handleSettlementGuide,
    trackContactSupport,
    onCreateTransfer,
    knowMore,
    sameDaySettlement,
    settlementClose,
    capturePayment,
    onActionSideBar,
  } = track;
  init({ someKey: 'someValue' });
  test('should send analytics event when action is performed on payment details screen', () => {
    [paymentDetails, paymentDetailsSidebar].forEach((fun) => {
      fun('objectName');
      expect(analyticsTrack).toHaveBeenCalledWith({
        actionName: 'clicked',
        objectName: 'objectName',
        properties: {
          someKey: 'someValue',
        },
        screen: 'payments',
        toLumberjack: false,
      });
    });
  });

  test('should send analytics event when action is performed on the product details screen', () => {
    [paymentDetailsUnmount, settlementOverView, methodViewed].forEach((fun) => {
      fun('objectName', 'productType');
      expect(analyticsTrack).toHaveBeenCalledWith({
        actionName: 'clicked',
        objectName: 'objectName',
        properties: {
          location: 'productType',
          someKey: 'someValue',
        },
        screen: 'productType Details',
        toLumberjack: true,
      });
    });
  });

  test('should send analytics event when click action is performed on the objects', () => {
    [
      handleSettlementGuide,
      trackContactSupport,
      onCreateTransfer,
      knowMore,
      sameDaySettlement,
      settlementClose,
      capturePayment,
      onActionSideBar,
    ].forEach((fun) => {
      fun('objectName', 'screen');
      expect(analyticsTrack).toHaveBeenCalledWith({
        actionName: 'clicked',
        objectName: 'objectName',
        properties: {
          someKey: 'someValue',
        },
        screen: 'screen',
        toLumberjack: true,
      });
    });
  });
});
