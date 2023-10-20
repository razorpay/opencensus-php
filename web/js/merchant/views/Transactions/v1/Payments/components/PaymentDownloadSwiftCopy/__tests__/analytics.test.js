import { analyticsTrack } from 'common/utils/analytics';
import {
  trackDownloadButtonClicked,
  trackDownloadSuccess,
  trackDownloadFailure,
} from 'merchant/views/Transactions/v1/Payments/components/PaymentDownloadSwiftCopy/analytics';

jest.mock('common/utils/analytics', () => ({
  analyticsTrack: jest.fn(),
}));

const commonData = {
  objectName: 'download swift copy',
  screen: 'transactions',
  properties: {},
};

describe('Test trackDownloadButtonClicked', () => {
  test('should call the analyticsTrack function with the correct objectName and actionName', () => {
    trackDownloadButtonClicked();

    expect(analyticsTrack).toHaveBeenCalledWith({
      ...commonData,
      actionName: 'clicked',
    });
  });
});

describe('Test trackDownloadSuccess', () => {
  test('should call the analyticsTrack function with the correct objectName and actionName', () => {
    trackDownloadSuccess();

    expect(analyticsTrack).toHaveBeenCalledWith({
      ...commonData,
      actionName: 'response',
      properties: {
        success: '1',
      },
    });
  });
});

describe('Test trackDownloadFailure', () => {
  test('should call the analyticsTrack function with the correct objectName and actionName', () => {
    trackDownloadFailure();

    expect(analyticsTrack).toHaveBeenCalledWith({
      ...commonData,
      actionName: 'response',
      properties: {
        success: '0',
      },
    });
  });
});
