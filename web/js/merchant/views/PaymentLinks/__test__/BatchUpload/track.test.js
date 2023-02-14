import track from 'merchant/views/PaymentLinks/BatchUpload/track';
import * as analytics from 'common/utils/analytics';

const actionName = 'clicked';
const screen = 'Create Payment Link';
const properties = {
  userId: 'Unknown',
  mode: 'batch',
  userRole: 'Unknown',
  merchantId: 'Unknown',
};

describe('Batch Upload Track Unit Test Case', () => {
  beforeAll(() => {
    window.rzp_user = {};

    window.rzpQ = {
      component: jest.fn(),
      paymentLinks: () => ({
        interaction: jest.fn(),
      }),
      productOnboarding: () => ({
        success: jest.fn(),
      }),
    };
    jest.spyOn(analytics, 'analyticsTrack').mockImplementation(jest.fn);
  });

  const lumberjackTrackMock = jest.fn();
  track.init(lumberjackTrackMock);

  test('should track donwloadSampleInModal event', () => {
    track.donwloadSampleInModal();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'payment link create sample modal',
      actionName,
      screen,
      properties,
    });
  });

  test('should track onDocumentClickInModal event', () => {
    track.onDocumentClickInModal();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'payment link create docs modal',
      actionName,
      screen,
      properties,
    });
  });

  test('should track uploadClicked event', () => {
    track.uploadClicked();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'payment link create dropupload modal',
      actionName,
      screen,
      properties,
    });
  });

  test('should track fileUploadError event', () => {
    track.fileUploadError();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'payment link create dropupload modal error',
      actionName,
      screen,
      properties,
    });
  });

  test('should track fileUploadSuccess event', () => {
    track.fileUploadSuccess();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'payment link create dropupload modal success',
      actionName,
      screen,
      properties,
    });
  });

  test('should track onFileNameTrack event', () => {
    track.onFileNameTrack();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'payment link batch create name',
      actionName,
      screen,
      properties,
    });
  });

  test('should track onSmSNotify event', () => {
    track.onSmSNotify();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'payment link batch create sms',
      actionName,
      screen,
      properties,
    });
  });

  test('should track onEmailNotify event', () => {
    track.onEmailNotify();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'payment link batch create email',
      actionName,
      screen,
      properties,
    });
  });

  test('should track onPreview event', () => {
    track.onPreview();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'payment link batch create preview table',
      actionName: 'onMouseEnter',
      screen,
      properties,
    });
  });

  test('should track abandonBatchModal event', () => {
    track.abandonBatchModal();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'payment link batch create abandon',
      actionName,
      screen,
      properties,
    });
  });

  test('should track createBatch event', () => {
    track.createBatch();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'payment link batch create issue',
      actionName,
      screen,
      properties,
    });
  });

  test('should track successModalClose event', () => {
    track.successModalClose();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'payment link batch create close success',
      actionName,
      screen,
      properties,
    });
  });

  test('should track batchDetailViewOnLoad event', () => {
    track.batchDetailViewOnLoad();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'payment link batch detail view load',
      actionName,
      screen,
      properties,
    });
  });

  test('should track onDetailViewUnMount event', () => {
    track.onDetailViewUnMount();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'payment link batch detail close',
      actionName,
      screen,
      properties,
    });
  });

  test('should track onDetailsView event', () => {
    track.onDetailsView();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'payment link batch detail view load',
      actionName,
      screen,
      properties,
    });
  });

  test('should track viewAllClick event', () => {
    track.viewAllClick();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'payment link batch detail view all',
      actionName,
      screen,
      properties,
    });
  });

  test('should track batchIdChange event', () => {
    track.batchIdChange();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'payment link batch search batch id change',
      actionName,
      screen,
      properties,
    });
  });

  test('should track batchSearchCount event', () => {
    track.batchSearchCount();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'payment link batch search count',
      actionName,
      screen,
      properties,
    });
  });

  test('should track onSearchAnalytics event', () => {
    track.onSearchAnalytics();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'payment link batch search confirm',
      actionName,
      screen,
      properties,
    });
  });

  test('should track onClearAnalytics event', () => {
    track.onClearAnalytics();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'payment link batch search clear',
      actionName,
      screen,
      properties,
    });
  });

  test('should track onPagination event', () => {
    const pageNumber = 2;
    const pageType = 'Batch';
    track.onPagination(pageNumber, pageType);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: `payment link batch browse ${pageType}`,
      actionName,
      screen,
      properties: {
        userId: 'Unknown',
        mode: 'batch',
        userRole: 'Unknown',
        merchantId: 'Unknown',
        page: pageNumber,
      },
    });
  });
});
