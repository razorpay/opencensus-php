import track from 'merchant/views/PaymentButton/PaymentButton/List/track/index';
import * as analytics from 'common/utils/analytics';

const paymentButtonId = 'pb928';

describe('Payment Button List Track - UT', () => {
  beforeAll(() => {
    jest.spyOn(analytics, 'analyticsTrack').mockImplementation(jest.fn);
    window.rzpQ = {
      paymentButtons: () => ({
        interaction: jest.fn(),
      }),
    };
    const lumberjackTrackMock = jest.fn();
    track.init(lumberjackTrackMock);
  });

  test('should track payment button getCode event', () => {
    track.getCode(paymentButtonId);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      actionName: 'open',
      objectName: 'get code modal',
      properties: {
        button_id: paymentButtonId,
      },
      screen: 'list payment buttons',
      toCleverTap: false,
    });
  });

  test('should track payment button codeCopy event', () => {
    track.codeCopy(paymentButtonId);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'get code modal',
      actionName: 'copy code',
      screen: 'list payment buttons',
      toCleverTap: false,
      properties: { button_id: paymentButtonId },
    });
  });

  test('should track payment button openDocs event', () => {
    track.openDocs(paymentButtonId);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'get code modal docs',
      actionName: 'click',
      screen: 'list payment buttons',
      toCleverTap: false,
      properties: { button_id: paymentButtonId },
    });
  });

  test('should track payment button getCodeModalClosed event', () => {
    track.getCodeModalClosed(paymentButtonId);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'get code modal',
      actionName: 'close',
      screen: 'list payment buttons',
      toCleverTap: false,
      properties: { button_id: paymentButtonId },
    });
  });

  test('should track payment button createEnter event', () => {
    track.createEnter(paymentButtonId);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'create payment button',
      actionName: 'click',
      screen: 'list payment buttons',
      toCleverTap: true,
      properties: { section: 'list payment buttons' },
    });
  });

  test('should track payment button paginate event', () => {
    track.paginate({ skip: 1, count: 10 }, 'next');
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'browse next',
      actionName: 'click',
      screen: 'list payment buttons',
      toCleverTap: false,
      properties: { page: 1 },
    });
  });

  test('should track payment button errorCloseClick event', () => {
    track.errorCloseClick('pb_failed');
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'error',
      actionName: 'search',
      screen: 'list payment buttons',
      toCleverTap: false,
      properties: { error: 'pb_failed' },
    });
  });

  test('should track payment button searchClear event', () => {
    track.searchClear();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'search clear',
      actionName: 'click',
      screen: 'list payment buttons',
      toCleverTap: false,
      properties: {},
    });
  });

  test('should track payment button searchTitle event', () => {
    track.searchTitle();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'search title',
      actionName: 'input',
      screen: 'list payment buttons',
      toCleverTap: false,
      properties: {},
    });
  });

  test('should track payment button searchCount event', () => {
    track.searchCount();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'search count',
      actionName: 'input',
      screen: 'list payment buttons',
      toCleverTap: false,
      properties: {},
    });
  });
});
