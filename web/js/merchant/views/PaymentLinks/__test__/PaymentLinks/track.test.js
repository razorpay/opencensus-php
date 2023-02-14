import track from 'merchant/views/PaymentLinks/PaymentLinks/track';
import { titleCase } from 'common/utils/rzp-utils';
import * as analytics from 'common/utils/analytics';

const FEATURE_NAME = 'payment link';
const screen = 'payment link';
const actionName = 'clicked';

describe('Payment Link PL Track - UT', () => {
  beforeAll(() => {
    jest.spyOn(analytics, 'analyticsTrack').mockImplementation(jest.fn);
    window.rzpQ = {};
    window.rzpQ = {
      paymentLinks: () => ({
        success: jest.fn(),
        failed: jest.fn(),
      }),
    };
  });
  const lumberjackMock = () => jest.fn();
  track.init(lumberjackMock);

  test('should track "onNeedHelp" event', () => {
    track.onNeedHelp(FEATURE_NAME);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: `${titleCase(FEATURE_NAME)} need help`,
      actionName,
      screen,
      properties: { origin: 'dashboard' },
    });
  });

  test('should track "onDocumentClick" event', () => {
    track.onDocumentClick(FEATURE_NAME);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: `${titleCase(FEATURE_NAME)} doucmentation`,
      actionName,
      screen,
      properties: { origin: 'dashboard' },
    });
  });

  test('should track "onReminderSettingClick" event', () => {
    track.onReminderSettingClick(FEATURE_NAME);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: `${titleCase(FEATURE_NAME)} reminder setting`,
      actionName,
      screen,
      properties: { origin: 'dashboard' },
    });
  });

  test('should track "onShareLinkSuccess" event', () => {
    track.onShareLinkSuccess();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'pl share link success',
      actionName,
      screen,
      properties: { mweb: true, origin: 'dashboard' },
    });
  });

  test('should track "searchStatus" event', () => {
    const params = {
      page: 2,
    };
    track.searchStatus(params);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'pl search status',
      actionName,
      screen,
      properties: { params, origin: 'dashboard' },
    });
  });

  test('should track "searchSubmit" event', () => {
    track.searchSubmit();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'pl search submit',
      actionName,
      screen,
      properties: { origin: 'dashboard' },
    });
  });

  test('should track "searchCurrency" event', () => {
    track.searchCurrency();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'pl search currency',
      actionName,
      screen,
      properties: { origin: 'dashboard' },
    });
  });

  test('should track "searchCount" event', () => {
    track.searchCount();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'pl search count',
      actionName,
      screen,
      properties: { origin: 'dashboard' },
    });
  });

  test('should track "clearSearch" event', () => {
    track.clearSearch();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'pl search clear',
      actionName,
      screen,
      properties: { origin: 'dashboard' },
    });
  });

  test('should track "paginate" event', () => {
    track.paginate('next', 2);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'pl browse next',
      actionName,
      screen,
      properties: { page: 2, type: 'next', origin: 'dashboard' },
    });
  });

  test('should track "searchError" event', () => {
    const response = {
      error: {
        message: 'NO API found',
      },
    };
    track.searchError(response);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'pl search error',
      actionName,
      screen,
      properties: { response, origin: 'dashboard' },
    });
  });
});
