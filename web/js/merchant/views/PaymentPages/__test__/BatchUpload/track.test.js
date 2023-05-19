import track from 'merchant/views/PaymentPages/BatchUpload/track';
import * as analytics from 'common/utils/analytics';

const actionName = 'clicked';
const screen = 'Create Batch Payment Page';
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
    };
    jest.spyOn(analytics, 'analyticsTrack').mockImplementation(jest.fn);
  });

  test('should track donwloadSampleInModal event', () => {
    track.donwloadSampleInModal();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Payment Page create sample modal',
      actionName,
      screen,
      properties,
      toLumberjack: true,
    });
  });

  test('should track onDocumentClickInModal event', () => {
    track.onDocumentClickInModal();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Payment Page create docs modal',
      actionName,
      screen,
      properties,
      toLumberjack: true,
    });
  });

  test('should track uploadClicked event', () => {
    track.uploadClicked();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Payment Page create dropupload modal',
      actionName,
      screen,
      properties,
      toLumberjack: true,
    });
  });

  test('should track fileUploadError event', () => {
    track.fileUploadError();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Payment Page create dropupload modal error',
      actionName,
      screen,
      properties,
      toLumberjack: true,
    });
  });

  test('should track fileUploadSuccess event', () => {
    track.fileUploadSuccess();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Payment Page create dropupload modal success',
      actionName,
      screen,
      properties,
      toLumberjack: true,
    });
  });

  test('should track onFileNameTrack event', () => {
    track.onFileNameTrack();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Payment Page batch create name',
      actionName,
      screen,
      properties,
      toLumberjack: true,
    });
  });

  test('should track onSmSNotify event', () => {
    track.onSmSNotify();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Payment Page batch create sms',
      actionName,
      screen,
      properties,
      toLumberjack: true,
    });
  });

  test('should track onEmailNotify event', () => {
    track.onEmailNotify();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Payment Page batch create email',
      actionName,
      screen,
      properties,
      toLumberjack: true,
    });
  });

  test('should track onPreview event', () => {
    track.onPreview();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Payment Page batch create preview table',
      actionName: 'onMouseEnter',
      screen,
      properties,
      toLumberjack: true,
    });
  });

  test('should track abandonBatchModal event', () => {
    track.abandonBatchModal();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Payment Page batch create abandon',
      actionName,
      screen,
      properties,
      toLumberjack: true,
    });
  });

  test('should track createBatch event', () => {
    track.createBatch();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Payment Page batch create issue',
      actionName,
      screen,
      properties,
      toLumberjack: true,
    });
  });

  test('should track successModalClose event', () => {
    track.successModalClose();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Payment Page batch create close success',
      actionName,
      screen,
      properties,
      toLumberjack: true,
    });
  });

  test('should track batchDetailViewOnLoad event', () => {
    track.batchDetailViewOnLoad();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Payment Page batch detail view load',
      actionName,
      screen,
      properties,
      toLumberjack: true,
    });
  });

  test('should track onDetailViewUnMount event', () => {
    track.onDetailViewUnMount();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Payment Page batch detail close',
      actionName,
      screen,
      properties,
      toLumberjack: true,
    });
  });

  test('should track onDetailsView event', () => {
    track.onDetailsView();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Payment Page batch detail view load',
      actionName,
      screen,
      properties,
      toLumberjack: true,
    });
  });

  test('should track viewAllClick event', () => {
    track.viewAllClick();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Payment Page batch detail view all',
      actionName,
      screen,
      properties,
      toLumberjack: true,
    });
  });

  test('should track batchIdChange event', () => {
    track.batchIdChange();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Payment Page batch search batch id change',
      actionName,
      screen,
      properties,
      toLumberjack: true,
    });
  });

  test('should track batchSearchCount event', () => {
    track.batchSearchCount();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Payment Page batch search count',
      actionName,
      screen,
      properties,
      toLumberjack: true,
    });
  });

  test('should track onSearchAnalytics event', () => {
    track.onSearchAnalytics();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Payment Page batch search confirm',
      actionName,
      screen,
      properties,
      toLumberjack: true,
    });
  });

  test('should track onClearAnalytics event', () => {
    track.onClearAnalytics();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Payment Page batch search clear',
      actionName,
      screen,
      properties,
      toLumberjack: true,
    });
  });

  test('should track onPagination event', () => {
    const pageNumber = 2;
    const pageType = 'Batch';
    track.onPagination(pageNumber, pageType);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: `Payment Page batch browse ${pageType}`,
      actionName,
      screen,
      toLumberjack: true,
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
