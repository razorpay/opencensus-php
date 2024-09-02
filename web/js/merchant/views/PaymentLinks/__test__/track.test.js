import track from 'merchant/views/PaymentLinks/track';
import * as analytics from 'common/utils/analytics';

describe('Payment Link Index Track - UT', () => {
  beforeAll(() => {
    jest.spyOn(analytics, 'analyticsTrack').mockImplementation(jest.fn);
    window.rzpQ = {
      paymentLinks: () => ({
        interaction: jest.fn(),
      }),
    };
  });

  test('should initialise track & track onOnBoardinglandingNext event', () => {
    const lumberjackMock = jest.fn();
    track.init(lumberjackMock);
    track.onOnBoardinglandingNext();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'onboarding introduction next success',
      actionName: 'click',
      screen: 'payment link',
      properties: { section: 'Payment Links Tour' },
    });
  });

  test('should track payment link onBoardingSuccess event', () => {
    track.onBoardingSuccess();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'payment links onboarding start',
      actionName: 'click',
      screen: 'payment link',
      properties: { section: 'Payment Links Tour' },
    });
  });
});
